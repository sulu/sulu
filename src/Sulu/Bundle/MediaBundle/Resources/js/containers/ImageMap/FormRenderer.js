// @flow
import React from 'react';
import type {Node} from 'react';
import {translate} from 'sulu-admin-bundle/utils/Translator';
import Form from 'sulu-admin-bundle/components/Form';
import Tabs from 'sulu-admin-bundle/components/Tabs';
import SingleSelect from 'sulu-admin-bundle/components/SingleSelect';
import Icon from 'sulu-admin-bundle/components/Icon';
import Button from './Button';
import type {Hotspot} from './types';
import formRendererStyles from './formRenderer.scss';

type Props = {
    children: ?Node,
    disabled: boolean,
    formTypes: {[string]: string},
    onHotspotAdd: () => void,
    onHotspotRemove: (index: number) => void,
    onHotspotSelect: (index: number) => void,
    onHotspotTypeChange: (index: number, type: string) => void,
    onTypeChange: (index: number, type: string) => void,
    selectedIndex: number,
    value: Array<Hotspot>,
};

const AVAILABLE_HOTSPOT_TYPES = ['circle', 'point', 'rectangle'];

export default class FormRenderer extends React.PureComponent<Props> {
    get selectedHotspot() {
        const {value, selectedIndex} = this.props;

        return value[selectedIndex];
    }

    handleTypeChange = (type: string) => {
        const {onTypeChange, selectedIndex} = this.props;

        onTypeChange(selectedIndex, type);
    };

    handleHotspotTypeChange = (type: string) => {
        const {onHotspotTypeChange, selectedIndex} = this.props;

        onHotspotTypeChange(selectedIndex, type);
    };

    handleHotspotRemove = () => {
        const {onHotspotRemove, selectedIndex} = this.props;

        onHotspotRemove(selectedIndex);
    };

    render() {
        const {value, onHotspotAdd, onHotspotSelect, selectedIndex, children, disabled, formTypes} = this.props;

        return (
            <Form>
                <Form.Field label={translate('sulu_media.hotspots')}>
                    <div className={formRendererStyles.container}>
                        <div className={formRendererStyles.toolbar}>
                            <Button disabled={disabled} icon="su-plus-circle" onClick={onHotspotAdd} />

                            {!value.length &&
                                <div className={formRendererStyles.emptyTabsLabel}>
                                    {translate('sulu_media.add_hotspot')}
                                </div>
                            }

                            <Tabs
                                className={formRendererStyles.tabs}
                                onSelect={onHotspotSelect}
                                selectedIndex={selectedIndex}
                                skin="light"
                                small={true}
                            >
                                {value.map((hotspot, index) => (
                                    <Tabs.Tab key={index}>{'#' + (index + 1).toString()}</Tabs.Tab>
                                ))}
                            </Tabs>
                        </div>

                        {!!value.length &&
                            <div className={formRendererStyles.content}>
                                <div className={formRendererStyles.settings}>
                                    <div className={formRendererStyles.form}>
                                        <Form>
                                            <Form.Field
                                                colSpan={5}
                                                label={translate('sulu_media.hotspot_type')}
                                                required={false}
                                                spaceAfter={1}
                                            >
                                                <SingleSelect
                                                    disabled={disabled}
                                                    onChange={this.handleHotspotTypeChange}
                                                    value={this.selectedHotspot.hotspot
                                                        && this.selectedHotspot.hotspot.type}
                                                >
                                                    {AVAILABLE_HOTSPOT_TYPES.map((value) => (
                                                        <SingleSelect.Option key={value} value={value}>
                                                            {translate('sulu_media.' + value)}
                                                        </SingleSelect.Option>
                                                    ))}
                                                </SingleSelect>
                                            </Form.Field>

                                            {Object.keys(formTypes).length > 1 &&
                                                <Form.Field
                                                    colSpan={5}
                                                    label={translate('sulu_media.form_type')}
                                                    required={false}
                                                    spaceAfter={1}
                                                >
                                                    <SingleSelect
                                                        disabled={disabled}
                                                        onChange={this.handleTypeChange}
                                                        value={this.selectedHotspot.type}
                                                    >
                                                        {Object.entries(formTypes).map(([key, value]) => (
                                                            <SingleSelect.Option key={key} value={key}>
                                                                {value}
                                                            </SingleSelect.Option>
                                                        ))}
                                                    </SingleSelect>
                                                </Form.Field>
                                            }
                                        </Form>
                                    </div>

                                    <button
                                        className={formRendererStyles.removeButton}
                                        disabled={disabled}
                                        onClick={this.handleHotspotRemove}
                                    >
                                        <Icon name="su-trash-alt" />
                                    </button>
                                </div>

                                {children}
                            </div>
                        }
                    </div>
                </Form.Field>
            </Form>
        );
    }
}
