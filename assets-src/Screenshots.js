import html2canvas from "html2canvas";
import Draw from "./Draw";

class Screenshots {
	static draw_canvas(element, canvas, config) {
		new Draw(element, canvas,
			{
				offsetX:config.offsetX===0?config.offsetX:parseInt(config.left.replace('px',''))-parseInt(window.scrollX),
				offsetY:config.offsetY===0?config.offsetY:parseInt(config.top.replace('px',''))-parseInt(window.scrollY)
			});
	}
	static uploadAttach() {

	}
	static capture = async () => {
		const canvas = document.createElement("canvas");
		const context = canvas.getContext("2d");
		const video = document.createElement("video");

		try {
			const captureStream = await navigator.mediaDevices.getDisplayMedia();
			video.srcObject = captureStream;
			context.drawImage(video, 0, 0, window.width, window.height);
			const frame = canvas.toDataURL("image/png");
			captureStream.getTracks().forEach(track => track.stop());
			window.location.href = frame;
		} catch (err) {
			console.error("Error: " + err);
		}
	}

	static html2canvas(screenshotTarget, config, promise) {
		html2canvas(screenshotTarget,
			{
				allowTaint: true,
				width: config.width.replace('px',''),
				height: config.height.replace('px',''),
				x: config.left.replace('px',''),
				y: config.top.replace('px',''),
				ignoreElements: function (element) {
					return element.id === 'feedback-app' || element.id === 'wpadminbar' || element.classList.contains('screenshot');
				}
			}
			).then((canvas) => {
			let el = document.getElementById('gs-screenshot')
			el.style.display='flex'
			let child = el.firstChild.firstChild
//			child.style.left=config.left;
//			child.style.top=config.top;
			child.style.height = config.height;
			child.style.width = config.width;
			if(child.childNodes.length) {
				child.childNodes[0].remove()
			}
			child.prepend(canvas);
			promise(child, canvas, config);
//			window.location.href = canvas.toDataURL("image/png");
		});
	}
}

export default Screenshots;
