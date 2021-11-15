import React from "react";
import Helper from "../Helper";
import Comment from "./Comment";
import Spinner from "./Spinner";
class Comments extends React.Component {
	constructor(props) {
		super(props)
		this.handleSubmit = this.handleSubmit.bind(this);
		this.handleChange = this.handleChange.bind(this);
		this.feedbackID = props.feedbackID;
		this.comments = props.comments;
		this.state = {comments:this.comments,submitting:false}
	}

	handleChange(event) {
		this.state.value = event.target.value;
		event.target.parentNode.dataset.replicatedValue = event.target.value;
	}

	handleSubmit(e) {
		e.preventDefault();
		let that = this;
		let feedback = {};
		feedback.post=this.feedbackID;
		feedback.content=this.state.value;
		feedback.meta = {};
		feedback.status='approved';
		feedback.meta.browser_info = Helper.getBrowserInfo();
		let request = new Request(gs_sf_data.endpoint_comments, {
				method: 'POST',
				headers: new Headers({
					'Content-Type' : 'application/json',
					'X-WP-Nonce': gs_sf_data.nonce,
				}),
				body: JSON.stringify(feedback)
			}

		);
		this.state.submitting = true;
		fetch(request).then(res => res.json()).then(comment => {
			that.state.submitting = false;
			if(comment.data && comment.data.status !== 201) {
				return;
			}
			comment.current = true;
			that.state.comments.push(comment);
			that.state.value = '';
			that.setState(that.state);
			that.textarea.value = '';
			this.commentsEl.scrollTop=this.commentsEl.scrollTopMax;
		});
	}

	render() {

		return <div className={'comments-area'}>
			<h3>Comments</h3>
			<div ref={el => this.commentsEl = el} className={'comments'}>
				{this.state.comments.map((comment) => (
					<Comment comment={comment} />
				))}
			</div>
			<form onSubmit={this.handleSubmit}>
				<section className="textarea">
					<div className={"grow-wrap"}>
                        <textarea ref={el => this.textarea = el} aria-label="Reply to feedback" placeholder="Reply to feedback ..."
                                  id="feedback-comment-textarea" onChange={this.handleChange} />
					</div>
					{ this.state.submitting === false && <button type={"submit"} className={"feedback-button"}>Add Reply</button> }
					{ this.state.submitting === true && <Spinner/>}
				</section>
			</form>
		</div>
	}
}

export default Comments;
