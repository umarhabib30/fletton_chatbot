@extends('layouts.admin')
@section('style')
<style>
    .col-form-label{
        color: #1A202C !important;
        font-weight: 600 !important;
    }
    .credentials{
        font-size: 22px;
        color: #1A202C;
        font-weight: 600;
    }
</style>
@endsection
@section('content')
<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <h5 class="card-header "><span class="credentials">Credentials</span> </h5>
            <div class="card-body">

                <form action="{{ route('admin.credentials.update') }}" method="POST">
                    @csrf
                    {{-- OpenAI Key --}}
                    <div class="mb-3 row">
                       <label class="col-md-1 col-form-label text-nowrap">OpenAI Key</label>
                        <div class="col-md-11">
                            <input type="text" name="open_ai_key" class="form-control" value="{{ $credentials->open_ai_key }}" placeholder="Enter OpenAI API Key">
                        </div>
                    </div>

                    {{-- GPT Assistant ID --}}
                    <div class="mb-3 row">
                        <label class="col-md-1 col-form-label text-nowrap">GPT Assistant ID</label>
                        <div class="col-md-11">
                            <input type="text" name="assistant_id" class="form-control" value="{{ $credentials->assistant_id }}" placeholder="Enter Assistant ID">
                        </div>
                    </div>

                    {{-- Twilio SID --}}
                    <div class="mb-3 row">
                        <label class="col-md-1 col-form-label text-nowrap">Twilio SID</label>
                        <div class="col-md-11">
                            <input type="text" name="twilio_sid" class="form-control" value="{{ $credentials->twilio_sid }}" placeholder="Enter Twilio SID">
                        </div>
                    </div>

                    {{-- Twilio Token --}}
                    <div class="mb-3 row">
                        <label class="col-md-1 col-form-label text-nowrap">Twilio Token</label>
                        <div class="col-md-11">
                            <input type="text" name="twilio_token" class="form-control" value="{{ $credentials->twilio_token }}" placeholder="Enter Twilio Token">
                        </div>
                    </div>

                    {{-- Twilio WhatsApp --}}
                    <div class="mb-3 row">
                        <label class="col-md-1 col-form-label text-nowrap">Twilio WhatsApp</label>
                        <div class="col-md-11">
                            <input type="text" name="twilio_whats_app" class="form-control" value="{{ $credentials->twilio_whats_app }}" placeholder="Enter WhatsApp Number">
                        </div>
                    </div>

                    {{-- Keap API Key --}}
                    <div class="mb-3 row">
                        <label class="col-md-1 col-form-label text-nowrap">Keap API Key</label>
                        <div class="col-md-11">
                            <input type="text" name="keap_api_key" class="form-control" value="{{ $credentials->keap_api_key }}" placeholder="Enter Keap API Key">
                        </div>
                    </div>

                    {{-- Update Button --}}
                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-primary px-4">Update</button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>
@endsection
