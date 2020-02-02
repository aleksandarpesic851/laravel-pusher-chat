@php
    use \App\Http\Controllers\ChatkitController;    
    if (\Route::current()->getName() != 'chat') {
        $chatkitInfo = ChatkitController::getChatkitAlertInformation();
    }
@endphp

<link rel="stylesheet" href="{{ asset('chat/client_chat.css') }}">
<script>
    var chatkitInfo = @json($chatkitInfo);
    var rooms = chatkitInfo.rooms;
    var users = chatkitInfo.users;
    var curUser = users.find(user => user.id == chatkitInfo.chatId)                  // current user
    var chattingUser = users.find(user => user.id == rooms[0].member_user_ids.find(member => member != chatkitInfo.chatId));                               // chatting user
    var chatkitLocator = chatkitInfo.chatkitLocator;
    var chatkitUser;                                // chatkit user instance. fixed for current user
    var chatkitRoom;                                // chatkit room instance  dynamic for selecting user
</script>

<script src="{{ asset('chat/client_chat.js') }}"></script>
<div class="chat-icon">
    <img src="/chat/img/chat_icon.png" style="width: 40px; height: 40px;">
    <span class="unread-count" id="chat-unread-cnt">0</span>
</div>
<div class="chat-container">
    <div id="chat">
        <div class="chat-content">
            <div class="contact-profile">
                <img src="" id="avatar" />
                <p></p>
                <img src="/chat/img/typing.gif" class="typing"/>
                <img src="/chat/img/close.png" class="chat-close"/>
            </div>
            <div class="messages">
                <ul>
                </ul>
            </div>
            <div class="message-input">
                <div class="wrap">
                    <input type="text" placeholder="Write your message..." />
                    <button class="submit"><i class="fa fa-paper-plane" aria-hidden="true"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>