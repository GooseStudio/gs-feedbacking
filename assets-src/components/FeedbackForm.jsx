/*global gs_sf_data */

import React from "react";
import CloseButton from "./CloseButton";
import Helper from "../Helper";
import Spinner from "./Spinner";
import Screenshots from "../Screenshots";

class FeedbackForm extends React.Component {
    constructor(props) {
        super(props);
        this.props.enableTakeScreenShot = this.props.enableTakeScreenShot ?? true;
        this.handleSubmit = this.handleSubmit.bind(this);
        this.handleChange = this.handleChange.bind(this);
        this.handleSelect = this.handleSelect.bind(this);
        this.closeWindow = this.closeWindow.bind(this)
        this.takeScreenshot = this.takeScreenshot.bind(this)
        this.attachScreenshot = this.attachScreenshot.bind(this)
        this.state = {submitting:false,text:''}
        this.message = React.createRef();
        this.screenshot = React.createRef();
    }

    handleChange(event) {
        this.state.text = event.target.value
        event.target.parentNode.dataset.replicatedValue = event.target.value
    }

    handleSelect(event) {
        this.state.category = event.target.value;
    }

    handleSubmit(e) {
        e.preventDefault();
        let request = new XMLHttpRequest();
        let feedback = {};
        let text = this.state.text;
        this.state.submitting=true;
        request.open('POST', gs_sf_data.endpoint);
        request.setRequestHeader('Content-Type', 'application/json');
        request.setRequestHeader('X-WP-Nonce', gs_sf_data.nonce);
        feedback.title=text.replace(/(\r\n|\n|\r)/gm,"");
        feedback.title=feedback.title.substring(0,feedback.title.length>20?20:feedback.title.length);
        feedback.content=text;
        feedback.meta = {};
        feedback.status='publish';
        feedback.meta._element_path=Helper.getPath();
        feedback.meta._browser_info = Helper.getBrowserInfo();
        feedback.meta._url = Helper.getPageURL();
        feedback.screenshot = Helper.getScreenshot();
        request.send(JSON.stringify(feedback));
        Helper.setScreenshot(null)
        this.message.current.value = '';
        this.closeWindow();
    }

    closeWindow() {
        Helper.getPrevious().classList.remove('selected')
        Helper.getPrevious().classList.remove('hover')
        console.log(Helper.getPrevious())
        this.feedback = this.feedback ?? document.getElementsByClassName('gs-feedback-form')[0];
        this.feedback.classList.remove('show-window');
        document.body.classList.remove('show-feedback');
        document.body.style.cursor='pointer';
        Helper.setPrevious(null)
    }

    takeScreenshot() {
        document.body.classList.remove('feedback');
        document.body.classList.add('take-screenshot');
        this.feedback = this.feedback ?? document.getElementsByClassName('gs-feedback-form')[0];
        this.feedback.classList.remove('show-window');
        document.body.classList.add('disable-scroll');
        document.body.style.cursor = 'crosshair';
    }

    attachScreenshot(event) {
        if (!event.target.files || !event.target.files[0]) return;
        //const screenshot = <Screenshot />
        let screenshot = document.getElementById('gs-screenshot');
        const feedbackAppEl = document.getElementById('feedback-app');
        screenshot = screenshot ?? document.createElement('div');
        let wrapper = screenshot.children[0] ?? document.createElement('div');
        let canvas_wrapper = wrapper.children[0] ?? document.createElement('div');
        let canvas = canvas_wrapper.children[0] ?? document.createElement('canvas');
        let button = canvas_wrapper.children[1] ?? document.createElement('button');

        canvas.id = "CursorLayer";
        canvas.width = 720;
        canvas.height = 405;
        canvas.style.zIndex = '20000';
        canvas.style.backgroundColor='blue'

        button.innerText='Attach image'
        button.addEventListener('click', function() {
            Helper.setScreenshot(canvas.toDataURL());
            document.getElementById('gs-screenshot').style.display='none';
            document.body.classList.remove('attach-screenshot')
            document.body.classList.add('feedback')
        });
        screenshot.id='gs-screenshot';
        wrapper.classList.add('wrapper')
        screenshot.prepend(wrapper)

        canvas_wrapper.classList.add('canvas')
        canvas_wrapper.style.height='405px'
        wrapper.prepend(canvas_wrapper)/**/
        wrapper.append(button)
        /*ReactDOM.render(
            screenshot,
            feedbackAppEl
        );*/

        feedbackAppEl.append(screenshot);
        let ctx = canvas.getContext('2d')
        ctx.fillStyle = "blue";
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        screenshot.style.display='flex'
        let rect = wrapper.getBoundingClientRect();
        let config = {height:405,width:720, top:rect.top+'px',left:rect.left+'px',offsetX:0, offsetY:0};
        let child = screenshot.firstChild.firstChild
        child.style.height = config.height;
        child.style.width = config.width;
        if(child.childNodes.length) {
            child.childNodes[0].remove()
        }
        child.prepend(canvas);
        const FR = new FileReader();
        FR.addEventListener("load", (evt) => {
            const img = new Image();
            img.addEventListener("load", () => {
                ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
                this.drawImageScaled(img, ctx)
                Screenshots.draw_canvas(canvas_wrapper, canvas, config);
            });
            img.src = evt.target.result;/**/
        });
        FR.readAsDataURL(event.target.files[0]);/**/
    }
    drawImageScaled(img, ctx) {
        let canvas = ctx.canvas ;
        let hRatio = canvas.width  / img.width    ;
        let vRatio =  canvas.height / img.height  ;
        let ratio  = Math.min ( hRatio, vRatio );
        let centerShift_x = ( canvas.width - img.width*ratio ) / 2;
        let centerShift_y = ( canvas.height - img.height*ratio ) / 2;
        ctx.clearRect(0,0,canvas.width, canvas.height);
        ctx.drawImage(img, 0,0, img.width, img.height,
            centerShift_x,centerShift_y,img.width*ratio, img.height*ratio);
    }
    render() {
        return <form onSubmit={this.handleSubmit} data-html2canvas-ignore={"true"} >
            <div className={"gs-feedback gs-feedback-form"}>
                <div className={"inner"}>
                    <header>
                        <div>
                            <ul>
                                <li>Feedback</li>
                            </ul>
                        </div>
                        <CloseButton closeWindow={this.closeWindow} />
                    </header>
                    <div className={'info-wrapper'}>
                        <section className="textarea">
                            <div className={'input'}>
                                <label htmlFor={'feedback-category'}>
                                    Category:
                                </label>
                                <select id={'feedback-category'} name={'category'} onChange={this.handleSelect}>
                                    <option value={'design'}>Design</option>
                                    <option value={'function'}>Function</option>
                                    <option value={'text'}>Text</option>
                                </select>
                                <div className={'screenshot'}>
                                </div>
                                { this.props.enableTakeScreenShot && <button type={"button"} className={"feedback-button screenshot-button"} onClick={this.takeScreenshot}>Take screenshot</button>}

                                <label className={"feedback-button attach-screenshot-button"} >Attach screenshot
                                        <input ref={this.screenshot} id="upload" type="file" style={{display: 'none'}} onChange={this.attachScreenshot} />
                                </label>
                            </div>
                            <div className={"grow-wrap"}>
                            <textarea ref={this.message} aria-label="Leave feedback to website designer etc" placeholder="Leave feedback ..."
            id="feedback-comment-textarea" onChange={this.handleChange}/>
                            </div>
                            { this.state.submitting === false && <button type={"submit"} className={"feedback-button"}>Post Feedback</button>}
                            { this.state.submitting === true && <Spinner/>}
                        </section>
                    </div>
                </div>
            </div>
        </form>
    }
}

export default FeedbackForm
