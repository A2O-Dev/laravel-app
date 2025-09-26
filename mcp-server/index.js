import {
    McpServer,
    ResourceTemplate,
} from "@modelcontextprotocol/sdk/server/mcp.js";
import { StreamableHTTPServerTransport } from "@modelcontextprotocol/sdk/server/streamableHttp.js";
import { z } from "zod";
import mysql from "mysql2/promise";
import express from "express";
import dotenv from "dotenv";
import path from "path";
import { fileURLToPath } from "url";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
dotenv.config({ path: path.resolve(__dirname, "../.env") });

async function getServer() {
    const dbClient = await mysql.createConnection({
        host: process.env.DB_HOST,
        port: parseInt(process.env.DB_PORT, 10),
        user: process.env.DB_USERNAME,
        password: process.env.DB_PASSWORD,
        database: process.env.DB_DATABASE,
    });
    console.log(
        `Connected to MySQL database ${process.env.DB_DATABASE} at ${process.env.DB_HOST}:${process.env.DB_PORT}`
    );
    const server = new McpServer({ name: "db-mcp", version: "1.0.0" });

    server.registerTool(
        "query",
        {
            title: "SQL Query Tool",
            description: "Query a MySQL database",
            inputSchema: { query: z.string() },
        },
        async ({ query }) => {
            if (!query)
                return {
                    content: [{ type: "text", text: "Query is required" }],
                };
            try {
                const [rows] = await dbClient.query(query);
                return {
                    content: [
                        { type: "text", text: JSON.stringify(rows, null, 2) },
                    ],
                };
            } catch (err) {
                return {
                    content: [{ type: "text", text: `Error: ${err.message}` }],
                };
            }
        }
    );

    server.registerResource(
        "schema",
        new ResourceTemplate("mysql://tables", { list: undefined }),
        {
            title: "Tables MySQL",
            description: "All tables in the database",
        },
        async (uri) => {
            try {
                const [rows] = await dbClient.query("SHOW TABLES;");

                const tablas = rows.map((r) => Object.values(r)[0]);

                const contents = tablas.map((t) => ({
                    uri: `mysql://tables/${t}`,
                    text: t,
                }));

                return { contents };
            } catch (err) {
                return {
                    contents: [
                        { uri: uri.href, text: `Error: ${err.message}` },
                    ],
                };
            }
        }
    );

    server.registerTool(
        "ping",
        { title: "Ping", description: "Ping MCP" },
        async () => ({ content: [{ type: "text", text: "pong" }] })
    );

    return server;
}

async function main() {
    const app = express();
    app.use(express.json());
    app.post("/mcp", async (req, res) => {
        try {
            const server = await getServer();
            const transport = new StreamableHTTPServerTransport({
                sessionIdGenerator: undefined,
            });

            res.on("close", () => {
                transport.close();
            });

            await server.connect(transport);
            await transport.handleRequest(req, res, req.body);
        } catch (err) {
            console.error("Erro handling MCP request:", err);
            if (!res.headersSent) {
                res.status(500).json({
                    jsonrpc: "2.0",
                    error: { code: -32603, message: "Internal server error" },
                    id: null,
                });
            }
        }
    });
    const port = process.env.MCP_PORT || 5137;
    app.listen(port, () => {
        console.log(`MCP server running at http://localhost:${port}`);
    });
}

main().catch((err) => {
    console.error("Error starting MCP server:", err);
    process.exit(1);
});
