@extends('layout.admin')

@section('styles')
    <link href="{{ '/assets/components/casting/castingCard.style.css?v='. config('view.assets.version') }}" rel="stylesheet" type="text/css">
    <link href="{{ '/assets/components/loadPage/loadPage.css?v='. config('view.assets.version') }}" rel="stylesheet" type="text/css">
@endsection

@section('content')
    <casting-invites-page></casting-invites-page>
@endsection

@section('scripts')
    <script>
        var talent = {!! json_encode($talent->toArray(\App\Models\BaseModel::ARRAY_MIN)) !!};
    </script>
    <script type="text/javascript" src="{{ '/vendor/moment/moment.js' }}"></script>
    <!-- SERVICES -->
    <script type="text/javascript" src="{{ '/assets/services/util.service.js?v='. config('view.assets.version') }}"></script>
    <script type="text/javascript" src="{{ '/assets/services/castingTalent.service.js?v='. config('view.assets.version') }}"></script>

    <!-- COMPONENTS -->
    <script type="text/javascript" src="{{ '/assets/components/talentCasting/castingInvitesPage.component.js?v='. config('view.assets.version') }}"></script>
    <script type="text/javascript" src="{{ '/assets/components/talentCasting/talentCastingCardList.component.js?v='. config('view.assets.version') }}"></script>
    <script type="text/javascript" src="{{ '/assets/components/talentCasting/talentCastingCard.component.js?v='. config('view.assets.version') }}"></script>
    <script type="text/javascript" src="{{ '/assets/components/payment/tagPlan.component.js?v='. config('view.assets.version') }}"></script>
    <script type="text/javascript" src="{{ '/assets/components/loadPage/loadPage.component.js?v='. config('view.assets.version') }}"></script>

@endsection