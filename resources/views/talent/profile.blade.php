@extends('layout.admin')

@section('styles')
    <link href="{{ '/assets/components/talent/talentImageViewer.style.css?v='. config('view.assets.version') }}" rel="stylesheet" type="text/css">
    <link href="{{ '/vendor/ui-select/select.css' }}" rel="stylesheet" type="text/css">
    <link href="{{ '/vendor/angular-bootstrap-datetimepicker/datetimepicker.css' }}" rel="stylesheet" type="text/css">
    <link href="{{ '/assets/css/modal-image-viewer.css?v='.config('view.assets.version') }}" rel="stylesheet" type="text/css">
    <link href="{{ '/assets/css/attribute-select.css?v='. config('view.assets.version') }}" rel="stylesheet" type="text/css">
    <link href="{{ '/assets/components/loading/loading.css?v='. config('view.assets.version') }}" rel="stylesheet" type="text/css">
    <link href="{{ '/assets/components/loadPage/loadPage.css?v='. config('view.assets.version') }}" rel="stylesheet" type="text/css">
@endsection

@section('content')
    <talent-profile></talent-profile>
@endsection

@section('scripts')
    <script>
        var talent = {!! json_encode($talent->toArray(\App\Models\Talent::ARRAY_FULL)) !!};
        $(document).ready(function() {
            $('[data-toggle="tooltip"]').tooltip();
        });

        window.uploadConfig = {!! json_encode( config('services.upload.config') )!!};

    </script>

    <script src="https://player.vimeo.com/api/player.js"></script>
    <script src="https://www.youtube.com/iframe_api"></script>

    <script type="text/javascript" src="{{ '/vendor/angularjs/angular-sanitize.js' }}"></script>
    <script type="text/javascript" src="{{ '/vendor/ui-select/select.js' }}"></script>
    <script type="text/javascript" src="{{ '/vendor/ng-file-upload/ng-file-upload-shim.min.js' }}"></script>
    <script type="text/javascript" src="{{ '/vendor/ng-file-upload/ng-file-upload.min.js' }}"></script>
    <script type="text/javascript" src="{{ '/vendor/angular-date-time-input-1.2.1/src/dateTimeInput.js' }}"></script>
    <script type="text/javascript" src="{{ '/vendor/angular-bootstrap-datetimepicker/datetimepicker.js' }}"></script>
    <script type="text/javascript" src="{{ '/vendor/angular-bootstrap-datetimepicker/datetimepicker.templates.js' }}"></script>
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?libraries=places&key={{ config('services.google.api.js.key') }}"></script>
    <script type="text/javascript" src="{{ '/vendor/vsGoogleAutocomplete-0.5.0/dist/vs-google-autocomplete.min.js' }}"></script>
    <script type="text/javascript" src="{{ '/vendor/vsGoogleAutocomplete-0.5.0/dist/vs-autocomplete-validator.min.js' }}"></script>

    <script type="text/javascript" src="{{ '/vendor/angular-slider/rzslider.min.js' }}"></script>
    <script type="text/javascript" src="{{ '/assets/filters/age.filter.js?v='. config('view.assets.version') }}"></script>
    <script type="text/javascript" src="{{ '/assets/filters/unionType.filter.js?v='. config('view.assets.version') }}"></script>
    <script type="text/javascript" src="{{ '/assets/filters/paymentType.filter.js?v='. config('view.assets.version') }}"></script>
    <script type="text/javascript" src="{{ '/assets/filters/feetInches.filter.js?v='. config('view.assets.version') }}"></script>
    <script type="text/javascript" src="{{ '/assets/services/castingTalent.service.js?v='. config('view.assets.version') }}"></script>
    <script type="text/javascript" src="{{ '/assets/services/attributeGroup.service.js?v='. config('view.assets.version') }}"></script>
    <script type="text/javascript" src="{{ '/assets/services/castingAttributeValue.service.js?v='. config('view.assets.version') }}"></script>
    <script type="text/javascript" src="{{ '/assets/directives/imageOnLoad.directive.js?v='. config('view.assets.version') }}"></script>

    <script type="text/javascript" src="{{ '/assets/components/talent/talentImageViewer.component.js?v='. config('view.assets.version') }}"></script>
    <script type="text/javascript" src="{{ '/assets/components/attribute/attribute.component.js?v='. config('view.assets.version') }}"></script>
    <script type="text/javascript" src="{{ '/assets/components/imageViewer/imageViewer.component.js?v='. config('view.assets.version') }}"></script>
    <script type="text/javascript" src="{{ '/assets/components/payment/tagPlan.component.js?v='. config('view.assets.version') }}"></script>
    <script type="text/javascript" src="{{ '/assets/components/talentCasting/talentCastingCount.component.js?v='. config('view.assets.version') }}"></script>
    <script type="text/javascript" src="{{ '/assets/components/loading/loading.component.js?v='. config('view.assets.version') }}"></script>
    <script type="text/javascript" src="{{ '/assets/components/loadPage/loadPage.component.js?v='. config('view.assets.version') }}"></script>
    <script type="text/javascript" src="{{ '/assets/components/videoPlayer/videoPlayer.component.js?v='. config('view.assets.version') }}"></script>

@endsection