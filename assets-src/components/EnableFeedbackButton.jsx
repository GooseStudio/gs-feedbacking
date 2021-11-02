import React from "react";
class EnableFeedbackButton extends React.Component {
	constructor(props) {
		super(props);
		this.handleClick = this.handleClick.bind(this);
		this.body = document.getElementsByTagName('body')[0];
	}
	render() {
		return <button className="enable-feedback feedback-button" onClick={this.handleClick}>
			<svg width="38" height="35" viewBox="0 0 38 35" fill="none" xmlns="http://www.w3.org/2000/svg">
				<g clipPath="url(#clip0)">
					<rect width="38" height="35"/>
					<path
						d="M23 4H7C4.23858 4 2 6.23858 2 9V28C2 30.7614 4.23858 33 7 33H28C30.7614 33 33 30.7614 33 28V15"
						stroke="white" strokeWidth="4"/>
					<path
						d="M16.6738 19.7385L17.6722 23.1606L11.1753 25.1731L10.8051 24.9931L10.6697 24.6044L13.4317 18.3831L16.6738 19.7385Z"
						stroke="white" strokeWidth="2"/>
					<path d="M13.9054 18.1975L31.9977 2.1144L36.0077 6.62534L17.9154 22.7085" stroke="white"
					      strokeWidth="3"/>
				</g>
			</svg>
		</button>
	}

	handleClick() {
		this.body.classList.toggle('feedback');
        let anchors = document.getElementsByTagName("a");
        for (let i = 0; i < anchors.length; i++) {
            anchors[i].setAttribute('disabled', 'disabled');//.onclick = function() {return false;};
        }
    }
}
export default EnableFeedbackButton
