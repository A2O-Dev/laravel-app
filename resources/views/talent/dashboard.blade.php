@extends('layout.admin')

@section('content')
    <talent-dashboard></talent-dashboard>
@endsection

@section('scripts')
    <script type="text/javascript" src="{{ '/assets/components/talent/talentDashboard.component.js?v='. config('view.assets.version') }}"></script>
    <script type="text/javascript" src="{{ '/assets/components/payment/tagPlan.component.js?v='. config('view.assets.version') }}"></script>
    <script type="text/javascript" src="{{ '/assets/components/loadPage/loadPage.component.js?v='. config('view.assets.version') }}"></script>

@endsection