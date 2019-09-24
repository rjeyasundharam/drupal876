(function ($) {
 Drupal.behaviors.cc_users = {
   attach: function (context, settings) {
    "use strict";
	$(window).on("load",function () {
	    $(".trigger_popup_fricc").click(function(){
	       $('.hover_bkgr_fricc').show();
	    });
	    $('.hover_bkgr_fricc').click(function(){
	        $('.hover_bkgr_fricc').hide();
	    });
	    $('.popupCloseButton').click(function(){
	        $('.hover_bkgr_fricc').hide();
	    });
	});    
    	$("span.close-signup").click(function(){
    		$("div#signup_popup").remove();
    		$("div#signup_popup").hide();
    	});
    	$("span.close-result").click(function(){
    		$("div#result_popup").remove();
    		$("div#result_popup").hide();
    	})
    
    if (!$("span").hasClass("close-signup")) {
    }
}}})(jQuery, Drupal);// End of use strict