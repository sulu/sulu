// @flow
import React from 'react';
import type {Element} from 'react';
import {translate} from 'sulu-admin-bundle/utils/Translator';
import Form from 'sulu-admin-bundle/components/Form';
import Tabs from 'sulu-admin-bundle/components/Tabs';
import SingleSelect from 'sulu-admin-bundle/components/SingleSelect';
import Icon from 'sulu-admin-bundle/components/Icon';
import Button from './Button';
import type {Hotspot} from './types';
import formRendererStyles from './formRenderer.scss';

type Props = {
    children: Element<*>,
    disabled: boolean,
    onHotspotAdd: () => void,
    onHotspotRemove: (index: number) => void,
    onHotspotSelect: (index: number) => void,
    onHotspotTypeChange: (index: number, type: string) => void,
    selectedIndex: number,
    value: Array<Hotspot>,
};

const AVAILABLE_HOTSPOT_TYPES = ['circle', 'point', 'rectangle'];

export default class FormRenderer extends React.PureComponent<Props> {
    get selectedHotspot() {
        const {value, selectedIndex} = this.props;

        return value[selectedIndex];
    }

    handleHotspotTypeChange = (type: string) => {
        const {onHotspotTypeChange, selectedIndex} = this.props;

        onHotspotTypeChange(selectedIndex, type);
    };

    handleHotspotRemove = () => {
        const {onHotspotRemove, selectedIndex} = this.props;

        onHotspotRemove(selectedIndex);
    };

    render() {
        const {value, onHotspotAdd, onHotspotSelect, selectedIndex, children, disabled} = this.props;

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
                                                spaceAfter={1}
                                            >
                                                <SingleSelect
                                                    disabled={disabled}
                                                    onChange={this.handleHotspotTypeChange}
                                                    value={this.selectedHotspot.type}
                                                >
                                                    {AVAILABLE_HOTSPOT_TYPES.map((value) => (
                                                        <SingleSelect.Option key={value} value={value}>
                                                            {translate('sulu_media.' + value)}
                                                        </SingleSelect.Option>
                                                    ))}
                                                </SingleSelect>
                                            </Form.Field>
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
