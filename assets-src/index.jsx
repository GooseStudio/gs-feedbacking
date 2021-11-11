import React from 'react';
import ReactDOM from 'react-dom';
import App from "./components/App";
import Elements from "./Elements";
import Helper from "./Helper";
import Screenshots from "./Screenshots";

let feedbackForm;

let body = document.getElementsByTagName('body')[0];
let feedbackAppEl = document.getElementById('feedback-app');
window.onload = function () {
    let elements = document.getElementsByClassName('elementor-sticky');
    feedbackForm = feedbackForm ?? document.getElementsByClassName('gs-feedback-form')[0];
    for (let i = 0; i < elements.length; i++) {
        elements[i].classList.remove('elementor-sticky');
        elements[i].classList.remove('elementor-sticky--active');
        elements[i].style.position = 'absolute';
    }
}

document.addEventListener('click', function (e) {
    if(!document.body.classList.contains('feedback') ||
        document.body.classList.contains('show-feedback') ||
        e.target.classList.contains('counter') ||
        e.target.classList.contains('feedback-button') ||
        (e.target.parent && e.target.parent.classList.contains('feedback-button'))
    )
        return;
    e = e || window.event;
    e.preventDefault();
    let target = e.target || e.srcElement,
        feedbackForm = feedbackForm ?? document.getElementsByClassName('gs-feedback-form')[0];
    document.body.classList.add('show-feedback');

    if (Helper.getPrevious()) {
        Helper.getPrevious().classList.remove('selected')
    }
    if (target === Helper.getPrevious()) {
        Helper.setPrevious(null);
        return;
    }
    Helper.setPrevious(target);
    target.classList.add('selected')
    Helper.setPath(Elements.DOMPath.xPath(Helper.getPrevious()))
    feedbackForm.classList.add('show-window');
    feedbackForm.style.left = e.pageX + 'px';
    feedbackForm.style.top = e.pageY + 'px';
}, false);

let screenshot = false;
let dragging = false;
let isTakingScreenshot = false;
let enableScreenshot = false;
let mouse = {
    startX: 0,
    startY: 0,
}
document.addEventListener('click', function (e) {
    if (!body.classList.contains('take-screenshot') || isTakingScreenshot)
        return;
    e.preventDefault();
    if (!enableScreenshot) {
        enableScreenshot = true;
    }
    e = e || window.event;
    if (!screenshot) {
        screenshot = document.createElement('div');
        let button = document.createElement('button');
        button.textContent = 'Take screenshot';
        screenshot.append(button);
        feedbackAppEl.append(screenshot);
        button.addEventListener('click', function () {
            body.classList.remove('take-screenshot')
            screenshot.display = 'none'
            document.body.style.cursor = 'wait';
            document.body.classList.add('disable-scroll');
            setTimeout((function () {
                Screenshots.html2canvas(document.body, mouse, function (element, canvas, config) {

                    let button = document.createElement('button');
                    button.textContent = 'Attach screenshot';
                    document.body.style.cursor = 'default';
                    button.addEventListener('click', function () {
                        Helper.setScreenshot(canvas.toDataURL());
                        let screenshotImg = feedbackForm.querySelector('.screenshot');
                        let image = new Image();
                        image.id = "screenshot-preview";
                        image.src = canvas.toDataURL();
                        screenshotImg.appendChild(image);
                        document.getElementById('gs-screenshot').style.display = 'none';
                        document.body.classList.remove('attach-screenshot')
                        document.body.classList.add('feedback')
                        feedbackForm.classList.add('show-window');
                    });
                    wrapper.append(button);
                    Screenshots.draw_canvas(element, canvas, config);
                    body.classList.add('attach-screenshot')
                    body.classList.remove('disable-scroll');
                    screenshot.remove();
                })
            }), 10)
        });
        let taken_screenshot = document.createElement('div');
        taken_screenshot.id = 'gs-screenshot';
        let wrapper = document.createElement('div');
        wrapper.classList.add('wrapper')
        taken_screenshot.prepend(wrapper)
        let canvas_wrapper = document.createElement('div');
        canvas_wrapper.classList.add('canvas')
        wrapper.prepend(canvas_wrapper)
        feedbackAppEl.append(taken_screenshot);
        screenshot.id = 'gs-screenshot-area'
        screenshot.classList.add('screenshot-area')
    }
    screenshot.display = 'flex'
    mouse.startX = e.pageX;
    mouse.startY = e.pageY;
    screenshot.style.left = e.pageX + 'px';
    screenshot.style.top = e.pageY + 'px';
    screenshot.style.width = '0px';
    screenshot.style.height = '0px';
    body.classList.add('taking-screenshot')
    isTakingScreenshot = true;
}, false);

document.addEventListener('mousemove', function (e) {
    if (!dragging && !isTakingScreenshot)
        return;
    e.preventDefault();

    e = e || window.event;
    screenshot.style.width = Math.abs(mouse.startX - e.pageX) + 'px'
    screenshot.style.height = Math.abs(mouse.startY - e.pageY) + 'px'
    screenshot.style.top = (mouse.startY > e.pageY ? e.pageY : mouse.startY) + 'px';
    screenshot.style.left = (mouse.startX > e.pageX ? e.pageX : mouse.startX) + 'px';
    dragging = true;
}, false);

document.addEventListener('click', function (e) {
    if (!dragging || !isTakingScreenshot)
        return;
    e.preventDefault();
    dragging = false;
    body.classList.remove('taking-screenshot')
    isTakingScreenshot = false;
    document.body.style.cursor = 'crosshair';
    mouse.width = screenshot.style.width;
    mouse.height = screenshot.style.height;
    mouse.top = screenshot.style.top;
    mouse.left = screenshot.style.left;
}, false);


document.addEventListener("keydown", function (e) {
    if (e.code === "Escape") {
        if (body.classList.contains('take-screenshot')) {
            document.getElementById('gs-screenshot').style.display = 'none';
            body.classList.add('disable-scroll');
            body.classList.remove('attach-screenshot')
            body.classList.remove('take-screenshot')
            body.classList.remove('taking-screenshot')
            body.classList.add('feedback')
            document.body.style.cursor = 'default';
            isTakingScreenshot = false;
            dragging = false;
            enableScreenshot = false;
            screenshot.remove();
        }
    }
});

ReactDOM.render(<App ref={(feedbackApp) => {
    window.feedbackApp = feedbackApp
}}/>, feedbackAppEl);