function showMerchantPage() {
    if(jQuery("#payfort_merchant_page").size()) {
        jQuery( "#payfort_merchant_page" ).remove();
    }
    jQuery("#review-buttons-container .btn-checkout").hide();
    jQuery("#review-please-wait").show();
    var merchantPageUrl = jQuery('#payfort_payment_form').attr('action');
    jQuery('<iframe  name="payfort_merchant_page" id="payfort_merchant_page" height="550px" frameborder="0" scrolling="no" onload="pfIframeLoaded(this)" style="display:none"></iframe>').appendTo('#pf_iframe_content');
    jQuery('.pf-iframe-spin').show();
    jQuery('.pf-iframe-close').hide();
    jQuery( "#payfort_merchant_page" ).attr("src", merchantPageUrl);
    //jQuery( "#payfort_payment_form" ).attr("action", merchantPageUrl);
    jQuery( "#payfort_payment_form" ).attr("target","payfort_merchant_page");
    jQuery( "#payfort_payment_form" ).submit();
    //jQuery( "#div-pf-iframe" ).show();
    //fix for touch devices
    if (fnIsTouchDevice()) {
        setTimeout(function() {
            jQuery("html, body").animate({ scrollTop: 0 }, "slow");
        }, 1);
    }
}

//fix for touch devices
function fnIsTouchDevice() {
  return 'ontouchstart' in window        // works on most browsers 
      || navigator.maxTouchPoints;       // works on IE10/11 and Surface
}

function pfClosePopup() {
    jQuery( "#div-pf-iframe" ).hide();
    jQuery( "#payfort_merchant_page" ).remove();
    window.location = jQuery( "#payfort_cancel_url" ).val();
}
function pfIframeLoaded(ele) {
    jQuery('.pf-iframe-spin').hide();
    jQuery('.pf-iframe-close').show();
    jQuery('#payfort_merchant_page').show();
}