class Draw {
	constructor(element, canvas, config) {
		this.element = element;
		this.canvas = canvas;
		this.ctx=canvas.getContext("2d")
		if(config.offsetX && config.offsetY) {
			this.ctx.translate(config.offsetX, config.offsetY)
		}
		this.coord = { x: 0, y: 0 };
		this.start = this.start.bind(this)
		this.stop = this.stop.bind(this)
		this.reposition = this.reposition.bind(this)
		this.draw = this.draw.bind(this)
		this.getMousePos = this.getMousePos.bind(this)
		this.canvas.addEventListener("mousedown",this.start );
		this.canvas.addEventListener("mouseup",  this.stop);
	}

	getMousePos(evt) {
		let rect = this.canvas.getBoundingClientRect(); // abs. size of element
		return {
            x:(evt.pageX - rect.left),
            y:(evt.pageY - rect.top),
        };
	}
	reposition(event) {
		this.coord = this.getMousePos(event);
	}
	start(event) {
		this.reposition(event);
		this.canvas.addEventListener("mousemove", this.draw);

	}
	stop() {
		this.canvas.removeEventListener("mousemove", this.draw);
	}
	draw(event) {
		this.ctx.beginPath();
		this.ctx.lineWidth = 5;
		this.ctx.lineCap = "round";
		this.ctx.strokeStyle = "red";
		this.ctx.moveTo(this.coord.x, this.coord.y);
		this.reposition(event);
		this.ctx.lineTo(this.coord.x, this.coord.y);
		this.ctx.stroke();
	}
}

export default Draw