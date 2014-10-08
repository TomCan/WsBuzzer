<html><head><title>WebSocket</title>
<style type="text/css">
html,body {
	font:normal 0.9em arial,helvetica;
}
#log {
	height:100px;
	border:1px solid #7F9DB9; 
	overflow:auto;
}
#msg {
	width:400px;
}


.buzzer-back {
    display: inline-block;
    max-width: 100%;
    max-height: 100%;
}

.buzzer-back.state0, .state0 {
    background-color: #aaa;
}
.buzzer-back.state1, .state1 {
    background-color: #f00;
}
.buzzer-back.state2, .state2 {
    background-color: #0f0;
}

#buzzer {
    width: 100%;
}

.viewer-buzzer {
    display: inline-block;
}

.screen, #c-onsole {
    display: none;
}

</style>
    <script src="jquery.min.js"></script>

    <script type="text/javascript">

var socket;
var state;

function init() {
	var host = "ws://<?php echo $_SERVER["HTTP_HOST"]; ?>:9000/"; // SET THIS TO YOUR SERVER
    state = 0; // not logged in
	try {
		socket = new WebSocket(host);
		log('WebSocket - status '+socket.readyState);

		socket.onopen    = function(msg) { 
							   log("Welcome - status "+this.readyState);
                                // send login
                                send('A' + $('#username').val());
						   };

		socket.onmessage = function(msg) { 
							   log("Received: "+msg.data);

                                cmd = msg.data.substr(0, 1);
                                rst = msg.data.substr(1);

                                if (state == 0) {
                                    if (cmd == "B") {
                                        // send pass
                                        send('B' + $('#password').val());
                                    } else if (cmd == "C") {
                                        // login ok
                                        state = 1;
                                        $(".screen").hide();
                                        $("#login-screen").hide();
                                        $(".screen-"+rst).show();
                                    }

                                } else {

                                    // logged in

                                    switch(cmd) {

                                        case "J":
                                            // join
                                            bstate = rst.substr(0, 1);
                                            bname = rst.substr(1);
                                            buzz = $('#viewer-screen-buzzer').html();
                                            buzz = buzz.replace('#BUZZER-ID#', 'buzzer-id-'+bname);
                                            buzz = buzz.replace('#BUZZER-NAME#', bname);
                                            // remove in case already present (eg. disconnect did not fire)
                                            $('#buzzer-id-'+bname).remove();
                                            $('#viewer-screen').append(buzz);
                                            $('#buzzer-id-'+bname).addClass('state'+bstate);
                                            break;

                                        case "S":
                                            // status
                                            for (i=0;i<rst.length;i++) {
                                                console.log(i, rst.substr(i,1));
                                                $('#viewer-screen div:nth-child('+(i+1)+')').removeClass('state0').removeClass('state1').removeClass('state2').addClass('state'+rst.substr(i, 1));
                                            }

                                        case "L":
                                            // leave, remove buzzer from view
                                            $('#buzzer-id-'+rst).remove();
                                            break;

                                        case "Z":
                                            // buzzer state received
                                            // 0 - inactive
                                            // 1 - ready
                                            // 2 - won
                                            $('.buzzer-back').removeClass('state0').removeClass('state1').removeClass('state2').addClass('state'+rst);

                                    }

                                }

						   };
		socket.onclose   = function(msg) { 
							   log("Disconnected - status "+this.readyState); 
						   };
	}
	catch(ex){ 
		log(ex); 
	}
	$("msg").focus();
}

function send(msg){
	var msg;
	if(!msg) {
		alert("Message can not be empty"); 
		return; 
	}

	$('#msg').focus();
	try { 
		socket.send(msg); 
		log('Sent: '+msg); 
	} catch(ex) { 
		log(ex); 
	}
}
function quit(){
	if (socket != null) {
		log("Goodbye!");
		socket.close();
		socket=null;
	}
}

function reconnect() {
	quit();
	init();
}

// Utilities
//function $(id){ return document.getElementById(id); }

function log(msg){
    var l = $('#log');
    l.append("<br>"+msg);
    if(l.length) l.scrollTop(l[0].scrollHeight - l.height());
}

    $(document).ready(function() {
        $('#loginbutton').click(init);
        $('#resetbutton').click(function() { send('R'); } );
        $('#buzzer').click(function() { send('Z'); } );
    });

</script>

</head>
<body>

<div id="login-screen">
    Username
    <br /><input type="text" id="username" />
    <br />Password
    <br /><input type="password" id="password" />

    <br /><input type="button" id="loginbutton" value="Log in" />
</div>

<div id="buzzer-screen" class="screen screen-4">
    <div class="buzzer-back state0">
        <img src="button.png" id="buzzer">
    </div>
</div>

<div id="viewer-screen" class="screen screen-2">
</div>

<!-- placeholder -->
<div id="viewer-screen-buzzer" style="display: none;">
    <div id="#BUZZER-ID#" class="viewer-buzzer">
        <img src="button.png" width="100" />
        <br />#BUZZER-NAME#
    </div>
</div>
<!-- end placeholder -->

<div id="admin-screen" class="screen screen-1">
    <input type="button" id="resetbutton" value="Reset" />
</div>

<div id="console">
    <b>Debug console</b>
    <div id="log"></div>
    <button onclick="quit()">Quit</button>
    <button onclick="reconnect()">Reconnect</button>
</div>


</body>
</html>