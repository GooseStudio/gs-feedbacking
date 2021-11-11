import React from "react";
class DisableFeedbackButton extends React.Component {
	constructor(props) {
		super(props);
		this.handleClick = this.handleClick.bind(this);
	}

	render() {
		return <button className="disable-feedback feedback-button" onClick={this.handleClick}>
			<svg width="35" height="35" viewBox="0 0 35 35" fill="none" xmlns="http://www.w3.org/2000/svg">
				<g clipPath="url(#clip0)">
					<rect width="35" height="35"/>
					<rect x="-1.01089" y="3.33408" width="6.60172" height="46.212" rx="3.30086" transform="rotate(-45 -1.01089 3.33408)" fill="white"/>
					<rect x="3.65723" y="36.0109" width="6.60172" height="46.212" rx="3.30086" transform="rotate(-135 3.65723 36.0109)" fill="white"/>
				</g>
			</svg>
		</button>
	}
	handleClick() {
		document.body.classList.remove('feedback');
	}
}
export default DisableFeedbackButton
