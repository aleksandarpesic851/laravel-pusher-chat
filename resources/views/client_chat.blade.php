@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Client Live Chat</div>

                <div class="card-body">
                    You can chat with admin!
                </div>
            </div>
        </div>
    </div>
    @include('client_chat_content')
</div>
@endsection
