import React from "react";

class Screenshot extends React.Component {
	constructor(props) {
		super(props);
		this.canvas = React.createRef();
		this.wrapper = React.createRef();
	}
	getCanvas() {
		return this.canvas.current;
	}
	createImage(url) {
		return new Promise((resolve, reject) => {
			const image = new Image();
			image.addEventListener('load', () => resolve(image));
			image.addEventListener('error', error => reject(error));
			image.setAttribute('crossOrigin', 'anonymous');
			image.src = url;
		});
	}
	loadImage(evt) {
		const img = new Image();
		let ctx = this.canvas.getContext('2d');
		img.addEventListener("load", () => {
			ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
			this.drawImageScaled(img, ctx)
			//Screenshots.draw_canvas(canvas_wrapper, canvas, config);
		});
		img.src = evt.target.result;
	}
	render() {
		return <div>
			<div id={"gs-screenshot"} style={{display:"flex"}}>
				<div className={"wrapper"} ref={this.wrapper}>
					<div className={"canvas"}>
						<canvas id={"CursorLayer"} width={720} height={405} style={{zIndex:20000,backgroundColor:"blue"}} ref={this.canvas} />
					</div>
					<button>Attach screenshot</button>
				</div>
			</div>
		</div>
	}
}

export default Screenshot