import React from "react";
import DisableFeedbackButton from "./DisableFeedbackButton";
import EnableFeedbackButton from "./EnableFeedbackButton";
class FeedbackToggle extends React.Component {
	render() {
		return <div className={"gs-feedback-toggle"} data-html2canvas-ignore={"true"}>
			<EnableFeedbackButton />
			<DisableFeedbackButton />
		</div>
	}
}
export default FeedbackToggle
