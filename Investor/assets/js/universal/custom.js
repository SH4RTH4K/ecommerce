$(document).ready(function () {

    $("#owl-demo").owlCarousel({
        autoPlay: 3000,
        items: 8,
        itemsDesktop: [1170, 3],
        autoWidth: true,
        animateIn: true,
        animateOut: true,
        pagination: false,
        itemsDesktopSmall: [1170, 3]
    });

    $("#owl-demo2").owlCarousel({
        autoPlay: 5000,
        stopOnHover: true,
        navigation: false,
        paginationSpeed: 1000,
        goToFirstSpeed: 2000,
        singleItem: true,
        autoHeight: true,
        transitionStyle: "fade"
    });

    $("#owl-demo13").owlCarousel({
        autoPlay: 5000,
        stopOnHover: true,
        navigation: false,
        paginationSpeed: 1000,
        goToFirstSpeed: 2000,
        singleItem: true,
        autoHeight: true,
        pagination: false,
        transitionStyle: "goDown"
    });

    $("#owl-demo14").owlCarousel({
        autoPlay: 6000,
        stopOnHover: true,
        navigation: false,
        paginationSpeed: 1000,
        goToFirstSpeed: 2000,
        singleItem: true,
        autoHeight: true,
        pagination: false,
        transitionStyle: "backSlide"
    });

    $("#owl-demo3").owlCarousel({
        autoPlay: 5000,
        stopOnHover: true,
        navigation: false,
        paginationSpeed: 1000,
        goToFirstSpeed: 2000,
        singleItem: true,
        autoHeight: true,
        transitionStyle: "fadeUp"
    });

    $("#owl-demo4").owlCarousel({
        autoPlay: 5000,
        stopOnHover: true,
        navigation: false,
        paginationSpeed: 1000,
        goToFirstSpeed: 2000,
        singleItem: true,
        autoHeight: true,
        transitionStyle: "goDown"
    });

    $("#owl-demo5").owlCarousel({
        autoPlay: 8000,
        stopOnHover: true,
        navigation: false,
        paginationSpeed: 1000,
        goToFirstSpeed: 2000,
        singleItem: true,
        autoHeight: true,
        transitionStyle: "goDown"
    });

    $("#owl-demo6").owlCarousel({
        autoPlay: 5000,
        stopOnHover: true,
        navigation: false,
        paginationSpeed: 1000,
        goToFirstSpeed: 2000,
        singleItem: true,
        autoHeight: true,
        transitionStyle: "fadeUp"
    });

    $("#owl-demo7").owlCarousel({
        items: 4,
        lazyLoad: true,
        navigation: true,
        pagination: false,
    });

    $("#owl-demo8").owlCarousel({
        autoPlay: 5000,
        stopOnHover: true,
        singleItem: true,
        paginationSpeed: 1000,
        goToFirstSpeed: 2000,
        transitionStyle: "fade"
    });

    $("#owl-demo10").owlCarousel({
        autoPlay: 8000,
        stopOnHover: true,
        navigation: false,
        paginationSpeed: 1000,
        goToFirstSpeed: 2000,
        singleItem: true,
        autoHeight: true,
        transitionStyle: "backSlide"
    });

    var time = 7; // time in seconds

    var $progressBar,
            $bar,
            $elem,
            isPause,
            tick,
            percentTime;

    //Init the carousel
    $("#owl-demo9").owlCarousel({
        slideSpeed: 500,
        navigation: true,
        paginationSpeed: 500,
        singleItem: true,
        afterInit: progressBar,
        afterMove: moved,
        startDragging: pauseOnDragging
    });



    /*===Mobile Menu===*/
    $(".menu-icon").click(function () {
        $("body").toggleClass("sidebar-open");
    });


    var sync1 = $("#sync1");
    var sync2 = $("#sync2");

    sync1.owlCarousel({
        singleItem: true,
        slideSpeed: 1000,
        pagination: false,
        afterAction: syncPosition,
        responsiveRefreshRate: 200,
    });

    sync2.owlCarousel({
        items: 5,
        itemsDesktop: [1170, 5],
        itemsDesktopSmall: [979, 5],
        itemsTablet: [768, 3],
        itemsMobile: [479, 3],
        pagination: false,
        responsiveRefreshRate: 100,
        afterInit: function (el) {
            el.find(".owl-item").eq(0).addClass("synced");
        }
    });

    $("#sync2").on("click", ".owl-item", function (e) {
        e.preventDefault();
        var number = $(this).data("owlItem");
        sync1.trigger("owl.goTo", number);
    });

    $("#colosebut1").click(function () {
        $("#div1").fadeOut("slow");
    });
    $("#colosebut2").click(function () {
        $("#div2").fadeOut("slow");
    });
    $("#colosebut3").click(function () {
        $("#div3").fadeOut("slow");
    });
    $("#colosebut4").click(function () {
        $("#div4").fadeOut("slow");
    });

    //fency box
    $('.fancybox').fancybox({
        openEffect: 'elastic',
        openSpeed: 150,
        closeEffect: 'elastic',
        closeSpeed: 150,
        helpers: {
            title: {
                type: 'inside'
            }
        }
    });

    // fencybox for youtube
    $('.fancybox-media').attr('rel', 'media-gallery').fancybox({
        openEffect: 'none',
        closeEffect: 'none',
        prevEffect: 'none',
        nextEffect: 'none',
        arrows: false,
        helpers: {
            title: {
                type: 'inside'
            },
            media: {},
            buttons: {}
        }
    });

    // list view and grid view 
    $(".grid_view").click(function () {
        $(".event_grid_view").show("slow");
        $(".event_list_view").hide("slow");
    });
    $(".list_view").click(function () {
        $(".event_grid_view").hide("slow");
        $(".event_list_view").show("slow");
    });

    // lazy load
    $("img").lazyload({
        effect: "fadeIn",
        threshold: 200
    });

    // smart select box
    $(".smart_select").select2();

    /* Tabs */

    $('.tab ul.tabs').addClass('active').find('> li:eq(0)').addClass('current');

    $('.tab ul.tabs li a').click(function (g) {
        var tab = $(this).closest('.tab'),
                index = $(this).closest('li').index();

        tab.find('ul.tabs > li').removeClass('current');
        $(this).closest('li').addClass('current');

        tab.find('.tab_content').find('div.tabs_tab').not('div.tabs_tab:eq(' + index + ')').slideUp();
        tab.find('.tab_content').find('div.tabs_tab:eq(' + index + ')').slideDown();

        g.preventDefault();
    });

    /* Tour */

    $('.tour_content ul.tour').addClass('active').find('> li:eq(0)').addClass('current');

    $('.tour_content ul.tour li a').click(function (f) {
        var tour = $(this).closest('.tour_content'),
                index = $(this).closest('li').index();

        tour.find('ul.tour > li').removeClass('current');
        $(this).closest('li').addClass('current');

        tour.find('div.tour_box_content').find('div.tour_box').not('div.tour_box:eq(' + index + ')').slideUp();
        tour.find('div.tour_box_content').find('div.tour_box:eq(' + index + ')').slideDown();

        f.preventDefault();
    });
    /* Typed*/

//    $("#typed").typed({
//        strings: ["+ 880 2 8151196–7", "8144708-09"],
//        typeSpeed: 60,
//        backDelay: 1300,
//        loop: true,
//        contentType: 'html', // or text
//        // defaults to false for infinite loop
//        loopCount: true,
//        callback: function () {
//            foo();
//        },
//        resetCallback: function () {
//            newTyped();
//        }
//    });

//    $(".reset").click(function () {
//        $("#typed").typed('reset');
//    });

});

function syncPosition(el) {
    var current = this.currentItem;
    $("#sync2")
            .find(".owl-item")
            .removeClass("synced")
            .eq(current)
            .addClass("synced")
    if ($("#sync2").data("owlCarousel") !== undefined) {
        center(current)
    }

}

function center(number) {
    var sync2visible = sync2.data("owlCarousel").owl.visibleItems;

    var num = number;
    var found = false;
    for (var i in sync2visible) {
        if (num === sync2visible[i]) {
            var found = true;
        }
    }

    if (found === false) {
        if (num > sync2visible[sync2visible.length - 1]) {
            sync2.trigger("owl.goTo", num - sync2visible.length + 2)
        } else {
            if (num - 1 === -1) {
                num = 0;
            }
            sync2.trigger("owl.goTo", num);
        }
    } else if (num === sync2visible[sync2visible.length - 1]) {
        sync2.trigger("owl.goTo", sync2visible[1])
    } else if (num === sync2visible[0]) {
        sync2.trigger("owl.goTo", num - 1)
    }
}

function newTyped() {
    console.log("newTyped called");
}

function foo() {
    console.log("Callback");
}

function start() {
    //reset timer
    percentTime = 0;
    isPause = false;
    //run interval every 0.01 second
    tick = setInterval(interval, 10);
}

function interval() {
    if (isPause === false) {
        percentTime += 1 / time;
        $bar.css({
            width: percentTime + "%"
        });
        //if percentTime is equal or greater than 100
        if (percentTime >= 100) {
            //slide to next item 
            $elem.trigger('owl.next')
        }
    }
}

//pause while dragging 
function pauseOnDragging() {
    isPause = true;
}

//moved callback
function moved() {
    //clear interval
    clearTimeout(tick);
    //start again
    start();
}

//Init progressBar where elem is $("#owl-demo")
function progressBar(elem) {
    $elem = elem;
    //build progress bar elements
    buildProgressBar();
    //start counting
    start();
}

//create div#progressBar and div#bar then prepend to $("#owl-demo")
function buildProgressBar() {
    $progressBar = $("<div>", {
        id: "progressBar"
    });
    $bar = $("<div>", {
        id: "bar"
    });
    $progressBar.append($bar).prependTo($elem);
}