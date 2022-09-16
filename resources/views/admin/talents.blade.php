@extends('layout.admin')

@section('styles')
    <link href="{{ '/vendor/ui-select/select.css' }}" rel="stylesheet" type="text/css">
@endsection

@section('content')
    <div class="row">
        <div class="container col-md-12">
            <div class="row">
                <div class="col-md-12">
                    <h1 class="page-header">Talents</h1>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <talent-table></talent-table>
                </div>
            </div>
        </div>
    </div>

@endsection

@section("scripts")
    <script type="text/javascript" src="{{ '/assets/components/talent/talentTable.component.js?v='. config('view.assets.version') }}"></script>
    <script type="text/javascript" src="{{ '/assets/components/user/userForm.component.js?v='. config('view.assets.version') }}"></script>
    <script type="text/javascript" src="{{ '/assets/services/user.service.js?v='. config('view.assets.version') }}"></script>
@endsection