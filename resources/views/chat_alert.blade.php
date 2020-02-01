@php
    use \App\Http\Controllers\ChatkitController;    
    if (\Route::current()->getName() != 'chat') {
        $chatkitInfo = ChatkitController::getChatkitAlertInformation();
    }
@endphp

<li class="nav-item">
    <a class="nav-link" href="{{ route('chat') }}" style="position: relative; padding: 15px">
        <i class="fa fa-bell-o"></i>
        <span style="position: absolute; top: 9px; right: 7px; text-align: center; font-size: 9px; padding: 2px 3px; line-height: .9; background-color: rgb(221, 75, 57); color: white; display: none" id="chat-alert">0</span>
    </a>
</li>

<script>
    @if (isset($chatkitInfo))
        var chatkitInfo = @json($chatkitInfo);
    @endif
</script>