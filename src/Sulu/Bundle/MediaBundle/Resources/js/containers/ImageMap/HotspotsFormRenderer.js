// @flow
import React from 'react';
import type {Node} from 'react';
import {translate} from 'sulu-admin-bundle/utils';
import {Form, Icon, SingleSelect, Tabs} from 'sulu-admin-bundle/components';
import {observer} from 'mobx-react';
import {computed} from 'mobx';
import Button from './Button';
import type {Hotspot} from './types';
import hotspotsFormRendererStyles from './hotspotsFormRenderer.scss';

type Props = {
    children: ?Node,
    disabled: boolean,
    onHotspotAdd: () => void,
    onHotspotRemove: (index: number) => void,
    onHotspotSelect: (index: number) => void,
    onHotspotTypeChange: (index: number, type: string) => void,
    onTypeChange: (index: number, type: string) => void,
    selectedIndex: number,
    types: {[string]: string},
    value: Array<Hotspot>,
};

const AVAILABLE_HOTSPOT_TYPES = {
    circle: 'sulu_media.circle',
    point: 'sulu_media.point',
    rectangle: 'sulu_media.rectangle',
};

@observer
class HotspotsFormRenderer extends React.Component<Props> {
    @computed get selectedHotspot() {
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
        const {children, disabled, onHotspotAdd, onHotspotSelect, selectedIndex, types, value} = this.props;
        const tabType = 'inline';

        return (
            <Form>
                <Form.Field label={translate('sulu_media.hotspots')}>
                    <div className={hotspotsFormRendererStyles.hotspotsFormRenderer}>
                        <div className={hotspotsFormRendererStyles.toolbar}>
                            <Button disabled={disabled} icon="su-plus-circle" onClick={onHotspotAdd} />

                            {!value.length &&
                                <div className={hotspotsFormRendererStyles.emptyTabsLabel}>
                                    {translate('sulu_media.add_hotspot')}
                                </div>
                            }

                            <Tabs
                                className={hotspotsFormRendererStyles.tabs}
                                onSelect={onHotspotSelect}
                                selectedIndex={selectedIndex}
                                type={tabType}
                            >
                                {value.map((hotspot, index) => (
                                    <Tabs.Tab key={index} type={tabType}>{'#' + (index + 1)}</Tabs.Tab>
                                ))}
                            </Tabs>
                        </div>

                        {!!value.length &&
                            <div className={hotspotsFormRendererStyles.content}>
                                <div className={hotspotsFormRendererStyles.settings}>
                                    <div className={hotspotsFormRendererStyles.form}>
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
                                                    {Object.keys(AVAILABLE_HOTSPOT_TYPES)
                                                        .map((key) => (
                                                            <SingleSelect.Option key={key} value={key}>
                                                                {translate(AVAILABLE_HOTSPOT_TYPES[key])}
                                                            </SingleSelect.Option>
                                                        ))}
                                                </SingleSelect>
                                            </Form.Field>

                                            {Object.keys(types).length > 1 &&
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
                                                        {Object.entries(types).map(([key, value]) => (
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
                                        className={hotspotsFormRendererStyles.removeButton}
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

export default HotspotsFormRenderer;
