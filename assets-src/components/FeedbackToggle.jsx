import React from "react";
import DisableFeedbackButton from "./DisableFeedbackButton";
import EnableFeedbackButton from "./EnableFeedbackButton";
class FeedbackToggle extends React.Component {
    constructor(props) {
        super(props);
    }

    render() {
		return <div className={"gs-feedback-toggle"} data-html2canvas-ignore={"true"}>
			<EnableFeedbackButton onEnableClick={this.props.onEnableClick} />
			<DisableFeedbackButton />
		</div>
	}
}
export default FeedbackToggle
