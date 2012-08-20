$(document).ready(function() {
    $('.image_list .caption').css({top:'130px', opacity:0, display:'none'});
    $('.image_list a').mouseover(function(){
        $(this).next('.caption').css({display:'block'}).animate({top:'100px',opacity:1}, 250).children('.name').hide().slideDown(500);
    })
    $('.image_list .item').mouseleave(function(){
        $('.caption').css({opacity:0, top:'80px', display:'none'});
    });
});