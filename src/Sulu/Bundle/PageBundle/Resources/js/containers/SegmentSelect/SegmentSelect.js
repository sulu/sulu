// @flow
import React, {Fragment} from 'react';
import {computed} from 'mobx';
import webspaceStore from '../../stores/webspaceStore';
import WebspaceSegmentSelect from './WebspaceSegmentSelect';
import type {Webspace} from '../../stores/webspaceStore/types';
import type {Value} from './types';

type Props = {|
    disabled: ?boolean,
    onChange: (value: Value) => void,
    value: ?Value,
    webspace: ?string,
|};

export default class SegmentSelect extends React.Component<Props> {
    @computed get visibleWebspaces(): Array<Webspace> {
        const {webspace} = this.props;

        // we want to select a segment for each webspace on webspace-independent objects like articles
        const matchingWebspaces: Array<Webspace> = webspace
            ? [webspaceStore.getWebspace(webspace)]
            : webspaceStore.grantedWebspaces;

        return matchingWebspaces.filter((webspace) => webspace.segments.length > 0);
    }

    handleWebspaceSegmentChange = (webspaceKey: string, segment: ?string) => {
        const {onChange, value} = this.props;

        onChange({...value, [webspaceKey]: segment});
    };

    render() {
        const {disabled, value} = this.props;

        return (
            <Fragment>
                {this.visibleWebspaces.map((webspace) => {
                    return (
                        <WebspaceSegmentSelect
                            disabled={!!disabled}
                            key={webspace.key}
                            onChange={this.handleWebspaceSegmentChange}
                            value={value ? value[webspace.key] : undefined}
                            webspace={webspace}
                            webspaceNameVisible={this.visibleWebspaces.length > 1}
                        />
                    );
                })}
            </Fragment>
        );
    }
}
