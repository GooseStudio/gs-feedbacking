import React from "react";
class CloseButton extends React.Component {
	constructor(props) {
		super(props);
	}
	render() {
		return <button type={"button"} className={"close-window"} onClick={this.props.closeWindow}>
			<svg width="15" height="15" viewBox="0 0 35 35" fill="none" xmlns="http://www.w3.org/2000/svg">
				<g>
					<rect x="-1.01089" y="3.33408" width="6.60172" height="46.212" rx="3.30086"
					      transform="rotate(-45 -1.01089 3.33408)" fill="#490353"/>
					<rect x="3.65723" y="36.0109" width="6.60172" height="46.212" rx="3.30086"
					      transform="rotate(-135 3.65723 36.0109)" fill="#490353"/>
				</g>
			</svg>
		</button>
	}
}
export default CloseButton
