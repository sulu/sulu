import AbstractToolbarAction from "./AbstractToolbarAction";
import {translate} from "../../../utils/Translator";
import {action, observable} from "mobx";
import Overlay from "../../../components/Overlay"
import SingleSelect from "../../../components/SingleSelect"
import Form from "../../../components/Form"
import React, {Node} from "react";
import type, {Action, Size} from "../../../components/Overlay/types";
import exportToolbarActionStyles from './exportToolbarAction.scss';
import resourceEndpointRegistry from '../../../services/ResourceRequester/registries/ResourceEndpointRegistry';
import {buildQueryString} from '../../../utils/Request';

export default class ExportToolbarAction extends AbstractToolbarAction {
    @observable showOverlay = false;
    @observable delimiter = 0;
    @observable enclosure = 0;
    @observable escape = 0;
    @observable newLine = 0;


    getNode() {
        return (
            <Overlay
                title={translate('sulu_admin.export_overlay_title')}
                confirmDisabled={false}
                key="sulu_admin.export"
                size={"small"}
                confirmLoading={false}
                confirmText={translate('sulu_admin.export')}
                onConfirm={this.handleConfirm}
                open={this.showOverlay}
                onClose={this.handleClose}
            >
                <div className={exportToolbarActionStyles.overlay}>
                    <Form>
                        <Form.Section size={6}>
                            <Form.Field label={translate('sulu_admin.delimiter')} description={translate('sulu_admin.delimiter_description')}>
                                <SingleSelect value={this.delimiter} onChange={this.handleDelimiterChanged}>
                                    <SingleSelect.Option value={';'}>;</SingleSelect.Option>
                                    <SingleSelect.Option value={','}>,</SingleSelect.Option>
                                    <SingleSelect.Option value={'\\t'}>\t</SingleSelect.Option>
                                </SingleSelect>
                            </Form.Field>
                            <Form.Field label={translate('sulu_admin.enclosure')} description={translate('sulu_admin.enclosure_description')}>
                                <SingleSelect value={this.enclosure} onChange={this.handleEnclosureChanged}>
                                    <SingleSelect.Option value={'\"'}>"</SingleSelect.Option>
                                    <SingleSelect.Option value={' '}> </SingleSelect.Option>
                                </SingleSelect>
                            </Form.Field>
                        </Form.Section>
                        <Form.Section size={6}>
                            <Form.Field label={translate('sulu_admin.escape')} description={translate('sulu_admin.escape_description')}>
                                <SingleSelect value={this.escape} onChange={this.handleEscapeChanged}>
                                    <SingleSelect.Option value={'\\'}>\</SingleSelect.Option>
                                    <SingleSelect.Option value={'\"'}>"</SingleSelect.Option>
                                </SingleSelect>
                            </Form.Field>
                            <Form.Field label={translate('sulu_admin.new_line')} description={translate('sulu_admin.new_line_description')}>
                                <SingleSelect value={this.newLine} onChange={this.handleNewLineChanged}>
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

    @action handleDelimiterChanged = (value) => {
        this.delimiter = value;
    }
    @action handleEnclosureChanged = (value) => {
        this.enclosure = value;
    }
    @action handleEscapeChanged = (value) => {
        this.escape = value;
    }
    @action handleNewLineChanged = (value) => {
        this.newLine = value;
    }

    @action handleConfirm = (item: Object) => {
        console.log(resourceEndpointRegistry.getEndpoint(this.datagridStore.resourceKey) + ".csv" + buildQueryString(
          {
              flat: true,
              delimiter: this.delimiter,
              escape: this.escape,
              enclosure: this.enclosure,
              newLine: this.newLine
          }
      ));

        window.location.assign(resourceEndpointRegistry.getEndpoint(this.datagridStore.resourceKey) + ".csv" + buildQueryString(
            {
                flat: true,
                delimiter: this.delimiter,
                escape: this.escape,
                enclosure: this.enclosure,
                newLine: this.newLine
            }
        ));

       return
    };
}