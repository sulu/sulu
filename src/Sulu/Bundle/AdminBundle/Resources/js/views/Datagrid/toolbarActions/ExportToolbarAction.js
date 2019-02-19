// @flow
import {action, observable} from 'mobx';
import React from 'react';
import {translate} from '../../../utils/Translator';
import Overlay from '../../../components/Overlay';
import Form from '../../../components/Form';
import SingleSelect from '../../../components/SingleSelect';
import resourceEndpointRegistry from '../../../services/ResourceRequester/registries/ResourceEndpointRegistry';
import {buildQueryString} from '../../../utils/Request';
import exportToolbarActionStyles from './exportToolbarAction.scss';
import AbstractToolbarAction from './AbstractToolbarAction';

export default class ExportToolbarAction extends AbstractToolbarAction {
    @observable showOverlay = false;
    @observable delimiter: string;
    @observable enclosure: string;
    @observable escape: string;
    @observable newLine: string;

    getNode() {
        return (
            <Overlay
                confirmDisabled={false}
                confirmLoading={false}
                confirmText={translate('sulu_admin.export')}
                key="sulu_admin.export"
                onClose={this.handleClose}
                onConfirm={this.handleConfirm}
                open={this.showOverlay}
                size="small"
                title={translate('sulu_admin.export_overlay_title')}
            >
                <div className={exportToolbarActionStyles.overlay}>
                    <Form>
                        <Form.Section size={6}>
                            <Form.Field description={translate('sulu_admin.delimiter_description')} label={translate('sulu_admin.delimiter')}>
                                <SingleSelect onChange={this.handleDelimiterChanged} value={this.delimiter}>
                                    <SingleSelect.Option value=";">;</SingleSelect.Option>
                                    <SingleSelect.Option value=",">,</SingleSelect.Option>
                                    <SingleSelect.Option value={'\\t'}>\t</SingleSelect.Option>
                                </SingleSelect>
                            </Form.Field>
                            <Form.Field description={translate('sulu_admin.enclosure_description')} label={translate('sulu_admin.enclosure')}>
                                <SingleSelect onChange={this.handleEnclosureChanged} value={this.enclosure}>
                                    <SingleSelect.Option value={'"'}>"</SingleSelect.Option>
                                    <SingleSelect.Option value=" "> </SingleSelect.Option>
                                </SingleSelect>
                            </Form.Field>
                        </Form.Section>
                        <Form.Section size={6}>
                            <Form.Field description={translate('sulu_admin.escape_description')} label={translate('sulu_admin.escape')}>
                                <SingleSelect onChange={this.handleEscapeChanged} value={this.escape}>
                                    <SingleSelect.Option value={'\\'}>\</SingleSelect.Option>
                                    <SingleSelect.Option value={'"'}>\"</SingleSelect.Option>
                                </SingleSelect>
                            </Form.Field>
                            <Form.Field description={translate('sulu_admin.new_line_description')} label={translate('sulu_admin.new_line')}>
                                <SingleSelect onChange={this.handleNewLineChanged} value={this.newLine}>
                                    <SingleSelect.Option value={'\\n'}>\n</SingleSelect.Option>
                                    <SingleSelect.Option value={'\\r\\n'}>\r\n</SingleSelect.Option>
                                    <SingleSelect.Option value={'\\r'}>\r</SingleSelect.Option>
                                </SingleSelect>
                            </Form.Field>
                        </Form.Section>
                    </Form>
                </div>

            </Overlay>
        );
    }

    getToolbarItemConfig() {
        return {
            icon: 'su-download',
            label: translate('sulu_admin.export'),
            onClick: action(() => {
                this.showOverlay = true;
            }),
            type: 'button',
        };
    }

    @action handleClose = () => {
        this.showOverlay = false;
    };

    @action handleDelimiterChanged = (value: string) => {
        this.delimiter = value;
    };
    @action handleEnclosureChanged = (value: string) => {
        this.enclosure = value;
    };
    @action handleEscapeChanged = (value: string) => {
        this.escape = value;
    };
    @action handleNewLineChanged = (value: string) => {
        this.newLine = value;
    };

    @action handleConfirm = () => {
        console.log(resourceEndpointRegistry.getEndpoint(this.datagridStore.resourceKey) + '.csv' + buildQueryString(
            {
                flat: true,
                delimiter: this.delimiter,
                escape: this.escape,
                enclosure: this.enclosure,
                newLine: this.newLine,
            }
        ));

        window.location.assign(resourceEndpointRegistry.getEndpoint(this.datagridStore.resourceKey) + '.csv' + buildQueryString(
            {
                flat: true,
                delimiter: this.delimiter,
                escape: this.escape,
                enclosure: this.enclosure,
                newLine: this.newLine,
            }
        ));

        return;
    };
}
