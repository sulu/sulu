// @flow
import React from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import Preview from './Preview';
import 'sulu-admin-bundle/containers/Application/global.scss';
import previewStyles from './preview.scss';
import {webspaceStore} from 'sulu-page-bundle/stores';
import {buildQueryString, transformDateForUrl} from 'sulu-admin-bundle/utils';
import debounce from 'debounce';

const Props = {};

@observer
class PreviewApplication extends React.Component<Props> {
    static debounceDelay: number = 250;

    @observable webspaceKey: string;
    @observable webspaceOptions: Array<Object> = [];

    @observable segmentKey: string;
    @computed get segments() {
        if (!this.webspaceKey) {
            return [];
        }

        return webspaceStore.getWebspace(this.webspaceKey).segments;
    }

    @observable dateTime: Date;

    @observable iframeRef: ?HTMLIFrameElement;
    @observable reloadCounter: number = 0;

    @computed get renderRoute() {
        return document.location.href + '/render' + buildQueryString({
            webspaceKey: this.webspaceKey,
            segmentKey: this.segmentKey,
            dateTime: this.dateTime && transformDateForUrl(this.dateTime),
        });
    }

    constructor(props: Props) {
        super(props);

        this.webspaceOptions = webspaceStore.allWebspaces.map((webspace): Object => ({
            label: webspace.name,
            value: webspace.key,
        }));
        this.webspaceKey = this.webspaceOptions[0].value;

        this.dateTime = new Date();
    }

    @action handleSegmentChange = (segmentKey: ?string) => {
        this.segmentKey = segmentKey;
    };

    @action handleDateTimeChange = debounce((value: ?Date) => {
        this.dateTime = value || new Date();
    }, PreviewApplication.debounceDelay);

    @action handleWebspaceChange = (webspaceKey: string) => {
       this.webspaceKey = webspaceKey;
    };

    @action handleRefreshClick = () => {
        // We can not reload the iframe here as safari and firefox
        // resets the location.href to another url on previewDocument.open
        // so instead of this we rerender the whole iframe.
        ++this.reloadCounter;
    };

    @action setIframe = (iframeRef: ?Object) => {
        this.iframeRef = iframeRef;
    };

    render() {
        return (
            <div className={previewStyles.applicationContainer}>
                <Preview
                    webspace={this.webspaceKey}
                    webspaceOptions={this.webspaceOptions}
                    segments={this.segments}
                    segment={this.segmentKey}
                    targetGroup={null}
                    targetGroupOptions={[]}
                    size={null}
                    dateTime={this.dateTime}
                    renderRoute={this.renderRoute}
                    onPreviewWindowClick={null}
                    onSegmentChange={this.handleSegmentChange}
                    onTargetGroupChange={null}
                    onDateTimeChange={this.handleDateTimeChange}
                    onToggleSidebarClick={null}
                    onWebspaceChange={null /* TODO for e.g. articles */}
                    onRefreshClick={this.handleRefreshClick}
                >
                    <iframe
                        className={previewStyles.iframe}
                        key={this.reloadCounter}
                        ref={this.setIframe}
                        src={this.renderRoute}
                    />
                </Preview>
            </div>
        );
    }
}

export default PreviewApplication;
