/*global gs_sf_data  gs_sf_data.endpoint_comments */

import React from "react";
import CloseButton from "./CloseButton";
import parse from "html-react-parser";

import Comments from "./Comments";
import ReactDOM from "react-dom";

class FeedbackView extends React.Component {
    constructor(props) {
        super(props);
        this.closeWindowParent = props.closeWindow;//this.closeWindow.bind(this);
        this.feedback = props.feedback;
        this.onTabClick = this.onTabClick.bind(this);
        this.closeWindow = this.closeWindow.bind(this)
        this.state = {tab:'feedback'}
    }
    onTabClick(tab) {
        this.setState({tab: tab});
    }
    closeWindow(e) {
        const node = ReactDOM.findDOMNode(this);
        node.classList.remove('show-window');
        this.closeWindowParent(e);
    }

    render() {
        return <div className={"gs-feedback gs-feedback-view show-window"} style={this.styles}>
                <div className={"inner"}>
                    <header>
                        <div>
                            <ul>
                                <li className={this.state.tab==='feedback'?'active':''} onClick={() => this.onTabClick('feedback')}>Feedback</li>
                                <li className={this.state.tab==='screenshot'?'active':''} onClick={() => this.onTabClick('screenshot')}>Screenshot</li>
                                <li className={this.state.tab==='browser'?'active':''} onClick={() => this.onTabClick('browser')}>Browser</li>
                            </ul>
                        </div>
                        <CloseButton closeWindow={this.closeWindow} />
                    </header>
                    {this.state.tab==='feedback' &&
                        <div className={'info-wrapper'}>
                    <section className={'feedback'}>
                        <div className={'content'}>
                        {parse(this.feedback.content)}
                        </div>
                        <div className={'meta'}>
                            <p><span className={'author'}>{this.feedback.author} </span> - <span className={'date'}>{this.feedback.date}</span></p>
                        </div>
                    </section>
                            <Comments feedbackID={this.feedback.id} comments={this.feedback.comments} /></div>
                    }
                    {this.state.tab==='screenshot' && this.feedback.screenshot.thumbnail &&
                        <div className={'info-wrapper'}>
                        <img src={this.feedback.screenshot.thumbnail}  alt={'Screenshot of page area'}/>
                        </div>
                    }
                    {this.state.tab==='browser' &&
                        <div className={'info-wrapper'}>
                    <ul className="browser-info">
                        <li><span className="label">Browser:</span> <span
                            className="value">{this.feedback.browser_info.browser.name} {this.feedback.browser_info.browser.version}</span></li>
                        <li><span className="label">OS:</span> <span className="value">{this.feedback.browser_info.os.name} {this.feedback.browser_info.os.version}</span>
                        </li>
                        <li><span className="label">Platform:</span> <span className="value">{this.feedback.browser_info.platform.type} {this.feedback.browser_info.platform.vendor}</span>
                        </li>
                        <li><span className="label">Screen:</span> <span
                            className="value">{this.feedback.browser_info.screen.width}x{this.feedback.browser_info.screen.height}</span>
                        </li>
                        <li><span className="label">Resolution:</span> <span
                            className="value">{this.feedback.browser_info.resolution.width}x{this.feedback.browser_info.resolution.height}</span>
                        </li>
                        <li><span className="label">User Agent:</span><br/> <span
                            className="value">{this.feedback.browser_info.user_agent }</span></li>
                    </ul>
                        </div>
                    }
                </div>
            </div>
    }
}

export default FeedbackView
