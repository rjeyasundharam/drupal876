
var quizVar = setInterval(function() {
  quizTimer()
}, 1000);
var d = 0;

function quizTimer() {
  document.getElementById("timer").innerHTML = d++;
}
