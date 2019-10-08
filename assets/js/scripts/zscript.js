document.addEventListener("DOMContentLoaded",function(){
	"use strict";


	function iframeLazyLoad() {
		var iframeDefer = document.getElementsByTagName('iframe');
		for (var i=0; i<iframeDefer.length; i++) {
			if(iframeDefer[i].getAttribute('data-src')) {
				iframeDefer[i].setAttribute('src',iframeDefer[i].getAttribute('data-src'));
			}
		}
	}
	iframeLazyLoad();


	var isInViewport = function (elem) {
		var bounding = elem.getBoundingClientRect();
		return (
			bounding.top >= 0 &&
			bounding.left >= 0 &&
			bounding.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
			bounding.right <= (window.innerWidth || document.documentElement.clientWidth)
			);
	};


	// numbers counter
	var numberCounterElem = document.querySelector('.counters');
	var counterStarted = false; // to run only once
	window.addEventListener('scroll', function (event) {
		if (isInViewport(numberCounterElem) && !counterStarted) {
			counterStarted = true;
			var cu = new counterUp({});
			cu.start();
		}
	}, false);


	// faqAccordion
	var acc = document.getElementsByClassName("faqAccordion__question");
	var i;

	for (i = 0; i < acc.length; i++) {
		acc[i].addEventListener("click", function() {
			this.classList.toggle("active");
			var panel = this.nextElementSibling;
		});
	}

	// scrollintoview
	var elems = document.getElementsByClassName("answerbutton");
	var next_btns = document.getElementsByClassName("next-btn");
	var send_data_btns = document.getElementsByClassName("send_data");
	var k, m, o;

	for (k = 0; k < elems.length; k++) {
		elems[k].addEventListener("click", function() {
			var l = document.getElementById('myBar').getBoundingClientRect().top + window.scrollY - 100;
			window.scroll({
				top: l,
				behavior: 'smooth'
			});
		});
	}
	for (m = 0; m < next_btns.length; m++) {
		next_btns[m].addEventListener("click", function() {
			var l = document.getElementById('myBar').getBoundingClientRect().top + window.scrollY - 100;
			window.scroll({
				top: l,
				behavior: 'smooth'
			});
		});
	}
	for (o = 0; o < send_data_btns.length; o++) {
		send_data_btns[o].addEventListener("click", function() {
			var l = document.getElementById('myBar').getBoundingClientRect().top + window.scrollY - 100;
			window.scroll({
				top: l,
				behavior: 'smooth'
			});
		});
	}




	// modal
	var modalTrigger = document.getElementsByClassName("show-questionform");
	modalTrigger[0].addEventListener("click", function(e) {
		document.getElementById("question_modal").classList.remove('display-none');
		document.getElementById("question_modal").classList.add('modal-opened');
		// document.getElementsByClassName("modal_overlay")[0].classList.remove('display-none');
		document.getElementsByTagName('BODY')[0].classList.add('overflow-hidden')
	});

	var modal_close = document.getElementsByClassName("modal__close");
	modal_close[0].addEventListener("click", function(e) {
		document.getElementById("question_modal").classList.add('display-none');
		document.getElementById("question_modal").classList.remove('modal-opened');
		// document.getElementsByClassName("modal_overlay")[0].classList.add('display-none');
		document.getElementsByTagName('BODY')[0].classList.remove('overflow-hidden')
	});



	// Parallax effect
	var parallaxItems = document.querySelectorAll(".rellax");

	window.addEventListener("scroll", function() {

		parallaxItems.forEach(function(parallax) {
			var scrolledHeight = window.pageYOffset,
			limit= parallax.offsetTop + parallax.offsetHeight;

			if(scrolledHeight > parallax.offsetTop && scrolledHeight <= limit) {
				parallax.style.backgroundPositionY=  (scrolledHeight - parallax.offsetTop) /1.5+ "px";

			} else {
				parallax.style.backgroundPositionY=  "0";
			}
		});
	});



});
