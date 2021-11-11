import React from "react";
import FeedbackToggle from "FeedbackToggle";
import FeedbackForm from "./FeedbackForm";
import ReactDOM from "react-dom";
import Bubble from "./Bubble";

class App extends React.Component {
	constructor(props) {
		super(props);
        this.retrievePaths = this.retrievePaths.bind(this);
        this.retrievePaths = this.retrievePaths.bind(this);
        this.state = {
			path:''
		}
        document.addEventListener('mouseover',this.highlightElement)
        document.addEventListener('mouseout', this.highlightElement)
    }

    highlightElement (e) {
        this.feedbackForm = this.feedbackForm ?? document.getElementsByClassName('gs-feedback-form')[0];
        if(!document.body.classList.contains('feedback') || e.target.classList.contains('counter') || this.feedbackForm.classList.contains('show-window') || document.body.classList.contains('show-feedback'))
            return;
        e.target.classList.toggle('hover');
    }

    retrievePaths() {
        /* global gs_sf_data */
        fetch(gs_sf_data.endpoint + '/paths',
            {
                method: 'GET', // or 'PUT'
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': gs_sf_data.nonce,
                }
            }
        )
            .then((response) => response.json())
            .then((feedback) => {
                for (let i = 0; i < feedback.length; i++) {
                    if (feedback[i].path === '') {
                        continue;
                    }
                    let element = document.evaluate('/' + feedback[i].path, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null);
                    if (element.singleNodeValue === null) {
                        element = document.evaluate('/' + feedback[i].try_path, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null)
                    }
                    if (element.singleNodeValue === null) {
                        continue;
                    }

                    let elem = document.createElement('div');
                    elem.dataset.html2canvasIgnore = 'true';
                    element.singleNodeValue.prepend(elem);
                    ReactDOM.render(<Bubble feedbackID={feedback[i].ids} element={element.singleNodeValue}/>, elem);
                }
            });

    }

	setPath(path) {
		this.setState({path:path});
	}
	render() {
		return <div>
			<FeedbackToggle onEnableClick={this.retrievePaths} />
			<FeedbackForm />
			<div id={"feedback-view"} />
		</div>
	}
}
export default App;
