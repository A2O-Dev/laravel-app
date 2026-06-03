import { useState } from 'react';

export default function useForm(initialValues) {
    const [form, setForm] = useState(initialValues);
    const [errors, setErrors] = useState({});
    const [submitting, setSubmitting] = useState(false);

    const update = (event) => {
        setForm((current) => ({ ...current, [event.target.name]: event.target.value }));
    };

    const reset = () => setForm(initialValues);

    return { form, setForm, errors, setErrors, submitting, setSubmitting, update, reset };
}
