class Draw {
	constructor(element, canvas, config) {
		this.element = element;
		this.canvas = canvas;
		this.ctx=canvas.getContext("2d")
		if(config.offsetX && config.offsetY) {
			this.ctx.translate(config.offsetX, config.offsetY)
		}
		console.log(config);

		this.coord = { x: 0, y: 0 };
		this.start = this.start.bind(this)
		this.stop = this.stop.bind(this)
		this.reposition = this.reposition.bind(this)
		this.draw = this.draw.bind(this)
		this.getMousePos = this.getMousePos.bind(this)
		this.canvas.addEventListener("mousedown",this.start );
		this.canvas.addEventListener("mouseup",  this.stop);
		this.rect = this.canvas.getBoundingClientRect();
	}

	getMousePos(evt) {
		let //rect = this.canvas.getBoundingClientRect(), // abs. size of element
			scaleX = this.element.width / this.rect.width,    // relationship bitmap vs. element for X
			scaleY = this.element.height / this.rect.height;  // relationship bitmap vs. element for Y

		return {
			x:(evt.clientX - this.rect.left),
			y:(evt.clientY - this.rect.top)};
	}
	getCanvasCoords(screenX, screenY) {
		let matrix = this.ctx.getTransform();
		let imatrix = matrix.invertSelf();
		let x = screenX * imatrix.a + screenY * imatrix.c + imatrix.e;
		let y = screenX * imatrix.b + screenY * imatrix.d + imatrix.f;
		return {x:x, y:y};
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

/**
 let mouseX = 0;
let mouseY = 0;
let canvas;
let t = 0;

function  getMousePos(canvas, evt) {
  var rect = canvas.getBoundingClientRect(), // abs. size of element
      scaleX = canvas.width / rect.width,    // relationship bitmap vs. element for X
      scaleY = canvas.height / rect.height;  // relationship bitmap vs. element for Y

  return [
    (evt.clientX - rect.left) * scaleX,
    (evt.clientY - rect.top) * scaleY ];
}

handleMouse = function(e) {
  [ mouseX, mouseY ] = getMousePos(canvas, e);
}

start = function() {
  let body = document.getElementById("body");
  canvas = document.createElement('canvas');
  body.appendChild(canvas);

  canvas.addEventListener("mousemove", handleMouse);
  let rect = canvas.getBoundingClientRect();
  canvas.width = rect.width;
  canvas.height = rect.height;

  draw();
}

getCanvasCoords = function(ctx, screenX, screenY) {
  let matrix = ctx.getTransform();
  var imatrix = matrix.invertSelf();
  let x = screenX * imatrix.a + screenY * imatrix.c + imatrix.e;
  let y = screenX * imatrix.b + screenY * imatrix.d + imatrix.f;
  return [x, y];
}

draw = function() {
  let ctx = canvas.getContext("2d");
  ctx.resetTransform();
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  ctx.translate(150,150);
  ctx.rotate(t);
  ctx.fillStyle = 'black';
  ctx.font = '40px serif';
  ctx.fillText(mouseX.toFixed(0), 0, 0);
  ctx.beginPath();

  let x, y;
  [x, y] = getCanvasCoords(ctx, mouseX, mouseY);

  ctx.ellipse(x, y, 20, 10, t, -Math.PI, Math.PI);
  ctx.fill();

  t += 0.02;
  requestAnimationFrame(draw);
}
 */
