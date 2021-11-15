import React from "react";
import parse from "html-react-parser";

class Comment extends React.Component {
	constructor(props) {
		super(props)
		this.comment = props.comment;
	}

	render() {
		return <div className={'comment ' + (this.comment.current === true?'is-author':'')} id={this.comment.id}>
			<div className={'content'}>
				<span className={'author'}>{this.comment.author_name}: </span>{parse(this.comment.content.rendered ?? this.comment.content)}
			</div>
			<div className={'meta'}>
				<p><span className={'date'}>{this.comment.date}</span></p>
			</div>
		</div>
	}
}

export default Comment;
