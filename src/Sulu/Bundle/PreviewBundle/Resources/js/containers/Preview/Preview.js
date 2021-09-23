// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import {DatePicker, Form, Toolbar} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import previewStyles from './preview.scss';

type Props = {|
    webspace: ?string,
    webspaceOptions: Array,
    segments: Array,
    segment: string,
    renderRoute: string,
    size: ?string,
    dateTime: ?Date,
    targetGroupOptions: Array,
    targetGroup: ?number,
    onPreviewWindowClick: ?() => null,
    onSegmentChange: (segmentKey: ?string) => null,
    onTargetGroupChange: ?(targetGroupId: number) => null,
    onDateTimeChange: (value: ?Date) => null,
    onToggleSidebarClick: ?() => null,
    onWebspaceChange: ?(webspace: string) => null,
|};

@observer
class Preview extends React.Component<Props> {
    static audienceTargeting: boolean = false;

    availableDeviceOptions = [
        {label: translate('sulu_preview.auto'), value: 'auto'},
        {label: translate('sulu_preview.desktop'), value: 'desktop'},
        {label: translate('sulu_preview.tablet'), value: 'tablet'},
        {label: translate('sulu_preview.smartphone'), value: 'smartphone'},
    ];

    @observable selectedDeviceOption = this.availableDeviceOptions[0].value;

    contentDisposer: () => mixed;

    componentWillUnmount() {
        if (this.contentDisposer) {
            this.contentDisposer();
        }
    }

    @action handleDeviceSelectChange = (value: string | number) => {
        this.selectedDeviceOption = value;
    };

    render() {
        const {
            onPreviewWindowClick,
            onSegmentChange,
            onTargetGroupChange,
            onDateTimeChange,
            onToggleSidebarClick,
            onWebspaceChange,
            onRefreshClick,
            size,
            dateTime,
            webspaceOptions,
            webspace,
            targetGroupOptions,
            targetGroup,
            segments,
            segment,
            children,
        } = this.props;

        const containerClass = classNames(
            previewStyles.container,
            {
                [previewStyles[this.selectedDeviceOption]]: this.selectedDeviceOption,
            }
        );

        return (
            <div className={containerClass}>
                <div className={previewStyles.previewContainer}>
                    <div className={previewStyles.iframeContainer}>
                        {children}
                    </div>
                </div>
                <Toolbar skin="dark">
                    <Toolbar.Controls grow={true}>
                        {onToggleSidebarClick && <Toolbar.Button
                            icon={size === 'medium' ? 'su-arrow-left' : 'su-arrow-right'}
                            onClick={onToggleSidebarClick}
                        />}
                        <Toolbar.Items>
                            <Toolbar.Select
                                icon="su-expand"
                                onChange={this.handleDeviceSelectChange}
                                options={this.availableDeviceOptions}
                                value={this.selectedDeviceOption}
                            />
                            <Toolbar.Popover
                                icon="su-calendar"
                                label={(dateTime || new Date()).toLocaleString()}
                            >
                                {() => (
                                    <div className={previewStyles.dateTimeForm}>
                                        <Form skin="dark">
                                            <Form.Field
                                                description={translate('sulu_admin.preview_date_time_description')}
                                                label={translate('sulu_admin.preview_date_time')}
                                            >
                                                <DatePicker
                                                    onChange={onDateTimeChange}
                                                    options={{dateFormat: true, timeFormat: true}}
                                                    value={dateTime}
                                                />
                                            </Form.Field>
                                        </Form>
                                    </div>
                                )}
                            </Toolbar.Popover>
                            {onWebspaceChange &&
                                <Toolbar.Select
                                    icon="su-webspace"
                                    onChange={onWebspaceChange}
                                    options={webspaceOptions}
                                    value={webspace}
                                />
                            }
                            {!!onTargetGroupChange &&
                                <Toolbar.Select
                                    icon="su-user"
                                    onChange={onTargetGroupChange}
                                    options={
                                        [
                                            {label: translate('sulu_audience_targeting.no_target_group'), value: -1},
                                            ...targetGroupOptions,
                                        ]
                                    }
                                    value={targetGroup}
                                />
                            }
                            {segments.length > 0 &&
                                <Toolbar.Select
                                    icon="su-focus"
                                    onChange={onSegmentChange}
                                    options={
                                        segments.map(({title, key}) => ({
                                            label: title,
                                            value: key,
                                        }))
                                    }
                                    value={segment}
                                />
                            }
                            <Toolbar.Button
                                icon="su-sync"
                                onClick={onRefreshClick}
                            >
                                {translate('sulu_preview.reload')}
                            </Toolbar.Button>
                            {!!onPreviewWindowClick && <Toolbar.Button
                                icon="su-link"
                                onClick={onPreviewWindowClick}
                            >
                                {translate('sulu_preview.open_in_window')}
                            </Toolbar.Button>}
                        </Toolbar.Items>
                    </Toolbar.Controls>
                </Toolbar>
            </div>
        );
    }
}

export default Preview;
