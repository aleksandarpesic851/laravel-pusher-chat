var totalUnreadCnt = 0;

$(document).ready(function() {
    if (chatkitInfo) {
        initChatkitAlert();
    }
    updateUnreadCnt();
});

function updateUnreadCnt() {
    if (totalUnreadCnt > 0) {
        $("#chat-alert").show();
        $("#chat-alert").html(totalUnreadCnt);
    } else {
        $("#chat-alert").hide();
    }
}

function initChatkitAlert() {
    const tokenProvider = new Chatkit.TokenProvider({
        url: `/api/live_chat/authenticate`
    });
    const chatManager = new Chatkit.ChatManager({
        instanceLocator: chatkitInfo.chatkitLocator,
        userId: chatkitInfo.chatId,
        tokenProvider,
    });
    chatManager.connect()
        .then(user => {
            chatkitInfo.rooms.forEach(room => {
                user.subscribeToRoomMultipart({
                    roomId: room.id,
                    hooks: {
                        onMessage: message => {
                            if (message.id > room.cursors[chatkitInfo.chatId].position && message.senderId != chatkitInfo.chatId) {
                                showMessage(message);
                                totalUnreadCnt++;
                                updateUnreadCnt();
                            }
                        }
                    }
                });
            });
        })
        .catch(error => {
            console.log('Error on connection', error)
        });
}

function showMessage(message) {
    let sender = chatkitInfo.users.find(user => user.id == message.senderId);
    GrowlNotification.notify({
        title: sender.name,
        description: "<br>" + message['parts'][0]['payload']['content'] + "<br><br>" + (new Date(message.createdAt)).toLocaleString(),
        type: 'info',
        position: 'bottom-right',
        image: {
            visible: true,
            customImage: sender.avatar_url
        },
        closeTimeout: 5000
      });
}