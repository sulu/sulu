// @flow
import React, {Fragment} from 'react';
import {computed} from 'mobx';
import {SingleSelect} from 'sulu-admin-bundle/components';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import {translate} from 'sulu-admin-bundle/utils';
import fieldStyles from 'sulu-admin-bundle/components/Form/field.scss';
import webspaceStore from '../../../stores/webspaceStore';
import type {Webspace} from '../../../stores/webspaceStore/types';
import segmentSelectStyles from './segmentSelect.scss';

class WebspaceSegmentSelect extends React.Component<{
    disabled: boolean,
    onChange: (webspaceKey: string, segment: ?string) => void,
    value: ?string,
    webspace: Webspace,
}> {
    handleSelectChange = (value: ?string) => {
        const {onChange, webspace} = this.props;

        onChange(webspace.key, value);
    };

    render() {
        const {disabled, value, webspace} = this.props;

        return (
            <div className={segmentSelectStyles.webspaceSection}>
                <label className={fieldStyles.label}>
                    {translate('sulu_admin.segments')} ({webspace.name})
                </label>

                <SingleSelect
                    disabled={!!disabled}
                    onChange={this.handleSelectChange}
                    value={value}
                >
                    <SingleSelect.Option>
                        {translate('sulu_admin.none_selected')}
                    </SingleSelect.Option>
                    {webspace.segments.map(({key, title}) => (
                        <SingleSelect.Option key={key} value={key}>
                            {title}
                        </SingleSelect.Option>
                    ))}
                </SingleSelect>
            </div>
        );
    }
}

export default class SegmentSelect extends React.Component<FieldTypeProps<{ [webspaceKey: string]: ?string}>> {
    @computed get visibleWebspaces(): Array<Webspace> {
        const {formInspector} = this.props;

        // we want to select a segment for each webspace on webspace-independent objects like articles
        const matchingWebspaces: Array<Webspace> = formInspector.metadataOptions?.webspace
            ? [webspaceStore.getWebspace(formInspector.metadataOptions.webspace)]
            : webspaceStore.grantedWebspaces;

        return matchingWebspaces.filter((webspace) => webspace.segments.length > 0);
    }

    handleWebspaceSegmentChange = (webspaceKey: string, segment: ?string) => {
        const {onChange, onFinish, value} = this.props;

        onChange({...value, [webspaceKey]: segment});
        onFinish();
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
                        />
                    );
                })}
            </Fragment>
        );
    }
}
