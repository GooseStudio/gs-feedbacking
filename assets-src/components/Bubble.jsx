import React from "react";
import Helper from "../Helper";
import FeedbackView from "./FeedbackView";

class Bubble extends React.Component {
	constructor(props) {
		super(props);
		this.handleClick = this.handleClick.bind(this)
		this.closeWindow = this.closeWindow.bind(this)
		this.feedback = document.getElementsByClassName('gs-feedback-view')[0];
		this.element = props.element;
		this.feedbackIDs = props.feedbackID;
		this.counter = this.feedbackIDs.length;
		this.state={showForm:false,rendered:false, position: {}};
	}
	getOffset(el) {
		const rect = el.getBoundingClientRect();
		console.log(rect);
		console.log(window);
		return {
			left: rect.left + window.scrollX+35,
			top: rect.top + window.scrollY+20
		};
	}
	handleClick(event) {
        event.preventDefault();
		let prev = Helper.getPrevious()
		if(prev) {
			prev.classList.remove('selected')
			this.feedback = this.feedback ?? document.getElementsByClassName('gs-feedback-form')[0];
			this.feedback.classList.remove('show-window');
		}
        document.body.classList.add('show-feedback')
		Helper.setPrevious(this.element);
		this.element.classList.add('selected');
		this.state.position = this.getOffset(event.target.parentNode)
		if(this.state.rendered === true) {
			this.setState({showForm:true, rendered:true, feedback:this.state.feedback});
			return;
		}
		fetch(gs_sf_data.endpoint+'/frontend/?ids='+JSON.stringify(this.feedbackIDs),
			{
				method: 'GET', // or 'PUT'
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': gs_sf_data.nonce,
				}
			}
		)
		.then((response) => response.json())
		.then((feedbacks) => {
			this.setState({showForm:true,
				feedbacks:feedbacks,
				rendered:true
			});
		});
	}

	closeWindow() {
		Helper.getPrevious().classList.remove('selected')
        document.body.classList.remove('show-feedback')
        //this.feedback = this.feedback ?? document.getElementsByClassName('gs-feedback-view')[0];
		//this.feedback.classList.remove('show-window');
		Helper.setPrevious(null)
		this.setState({showForm:false, rendered:true});
	}

	render() {
		return <div><div className={"gs-feedback-bubble"}>
			<div className={"wrapper"} >
				<div className="inner" onClick={(e) => this.handleClick(e)}>
					<div className="counter">
						{this.counter}
					</div>
				</div>
				{this.state.showForm === true && this.renderAllFeedback()}
			</div>
		</div>
		</div>
	}

	renderAllFeedback() {
		return ReactDOM.createPortal(
			<div className={'feedback-list'} style={{top:this.state.position.top,left:this.state.position.left}}>
			{this.state.feedbacks.map((feedback) => <FeedbackView closeWindow={this.closeWindow} feedback={feedback} />)}
		</div>,    document.getElementById("feedback-view"))
	}
}
export default Bubble
