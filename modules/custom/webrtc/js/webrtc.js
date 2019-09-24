(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.webrtc = {
    attach: function (context, settings) {

      // Generate random room name if needed
      if (!location.hash) {
        location.hash = Math.floor(Math.random() * 0xFFFFFF).toString(16);
      }
      const roomHash = location.hash.substring(1);

      // TODO: Replace with your own channel ID
      const drone = new ScaleDrone('vbVJVrO3j7aNzRJw');
      // Room name needs to be prefixed with 'observable-'
      const roomName = 'observable-' + roomHash;
      const configuration = {
        iceServers: [{
          urls: 'stun:stun.l.google.com:19302'
        }]
      };
      let room;
      let pc;
      pc = new RTCPeerConnection(configuration);
      var moptions={
            audio: true,
            video: true,
        };
      function onSuccess() { };
      function onError(error) {
        console.error(error);
      };

      drone.on('open', error => {
        if (error) {
          return console.error(error);
        }
        room = drone.subscribe(roomName);
        room.on('open', error => {
          if (error) {
            onError(error);
          }
        });
        // We're connected to the room and received an array of 'members'
        // connected to the room (including us). Signaling server is ready.
        room.on('members', members => {
          console.log('MEMBERS', members);
          // If we are the second user to connect to the room we will be creating the offer
          // const isOfferer = members.length === 2;
          const isOfferer = members.length > 1;
          alert("IsOfferer"+isOfferer+" Members ="+members.length);
          
          startWebRTC(isOfferer,moptions);
        });
      });

      // Send signaling data via Scaledrone
      function sendMessage(message) {
        drone.publish({
          room: roomName,
          message
        });
      }

      function startWebRTC(isOfferer,moptions) {
        // pc = new RTCPeerConnection(configuration);

        // 'onicecandidate' notifies us whenever an ICE agent needs to deliver a
        // message to the other peer through the signaling server
        pc.onicecandidate = event => {
          if (event.candidate) {
            sendMessage({ 'candidate': event.candidate });
          }
        };

        // If user is offerer let the 'negotiationneeded' event create the offer
        if (isOfferer) {
          pc.onnegotiationneeded = () => {
            pc.createOffer().then(localDescCreated).catch(onError);
          }
        }

        // When a remote stream arrives display it in the #remoteVideo element
        pc.ontrack = event => {
          const stream = event.streams[0];

          if($.isArray(event)) {
            alert("Event is an array!");
          } else {
            if (!remoteVideo.srcObject || remoteVideo.srcObject.id !== stream.id) {
              remoteVideo.srcObject = stream;
            }
          }
        };

        navigator.mediaDevices.getUserMedia(moptions).then(stream => {
          // Display your local video in #localVideo element
          localVideo.srcObject = stream;
          // Add your stream to be sent to the conneting peer
          stream.getTracks().forEach(track => pc.addTrack(track, stream));
        }, onError);

        // Listen to signaling data from Scaledrone
        room.on('data', (message, client) => {
          // Message was sent by us
          if (client.id === drone.clientId) {
            return;
          }

          if (message.sdp) {
            // This is called after receiving an offer or answer from another peer
            pc.setRemoteDescription(new RTCSessionDescription(message.sdp), () => {
              // When receiving an offer lets answer it
              if (pc.remoteDescription.type === 'offer') {
                pc.createAnswer().then(localDescCreated).catch(onError);
              }
            }, onError);
          } else if (message.candidate) {
            // Add the new ICE candidate to our connections remote description
            pc.addIceCandidate(
              new RTCIceCandidate(message.candidate), onSuccess, onError
            );
          }
        });
      }

      function localDescCreated(desc) {
        pc.setLocalDescription(
          desc,
          () => sendMessage({ 'sdp': pc.localDescription }),
          onError
        );
      }
      $( "#hangupButton" ).once("hanguptogglebehave").click(function() {
        startWebRTC(false,moptions);
      });
      $( "#videotoggle" ).once("videotogglebehave").click(function() {
        if(moptions.video==true){
          moptions.video=false;
        }
        else{
          moptions.video=true;  
        }
        startWebRTC(true,moptions);
      });
      $( "#audiotoggle" ).once("videotogglebehave").click(function() {
        if(moptions.audio==true){
          moptions.audio=false;
        }
        else{
          moptions.audio=true;  
        }
        startWebRTC(true,moptions);
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
