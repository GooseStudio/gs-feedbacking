import Bowser from "bowser";

class Helper {
	static setPath(element) {
		this.path = element;
	}

	static getPath() {
		return this.path;
	}
	static setPrevious(element) {
		this.previous = element;
	}

	static getPrevious() {
		return this.previous;
	}

	static getPageURL() {
		return window.location.href;
	}

	static css(element, style) {
		for (let property in style) {
			element.style[property] = style[property];
		}
	}

	static getBrowserInfo() {
		let info = Bowser.parse(window.navigator.userAgent);
		info.screen = {};
		info.screen.width = window.screen.width;
		info.screen.height = window.screen.height;
		info.resolution = {};
		info.resolution.width = window.screen.width * window.devicePixelRatio;
		info.resolution.height = window.screen.height * window.devicePixelRatio;
		info.user_agent = navigator.userAgent;
		return info;
	}

	static getScreenshot() {
		return this.screenshot;
	}
	static setScreenshot(screenshot) {
		this.screenshot = screenshot;
	}
}

export default Helper
