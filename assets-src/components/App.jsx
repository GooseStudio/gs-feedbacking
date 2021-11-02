import React from "react";
import FeedbackToggle from "FeedbackToggle";
import FeedbackForm from "./FeedbackForm";

class App extends React.Component {
	constructor(props) {
		super(props);
		this.state = {
			path:''
		}
	}

	setPath(path) {
		this.setState({path:path});
	}
	render() {
		//<FeedbackForm path={this.state.path} />
		// 			<FeedbackView />
		return <div>
			<FeedbackToggle />
			<FeedbackForm />
			<div id={"feedback-view"} />
		</div>
	}
}
export default App;
