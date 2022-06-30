// @flow
import React from 'react';
import classNames from 'classnames';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import Popover from '../Popover';
import tooltipStyles from './tooltip.scss';
import type {ChildrenArray, Element, ElementRef} from 'react';

type Props = {|
    children: ChildrenArray<Element<*> | false>,
    label: string,
|};

@observer
class Tooltip extends React.Component<Props> {
    constructor(props: Props) {
        super(props);
    }

    @observable tooltipOpen: boolean = false;

    @observable tooltipRef: ?ElementRef<*>;

    @action setTooltipRef = (ref: ?ElementRef<*>) => {
        this.tooltipRef = ref;
    };

    @action handleEnter = () => {
        this.tooltipOpen = true;
    };

    @action handleLeave = () => {
        this.tooltipOpen = false;
    };

    render() {
        const {
            children,
            label,
        } = this.props;

        return (
            // eslint-disable-next-line jsx-a11y/no-static-element-interactions
            <span
                className={tooltipStyles.tooltipContainer}
                onBlur={this.handleLeave}
                onFocus={this.handleEnter}
                onMouseEnter={this.handleEnter}
                onMouseLeave={this.handleLeave}
                ref={this.setTooltipRef}
            >
                {
                    this.tooltipRef
                        && <Popover
                            anchorElement={this.tooltipRef}
                            backdrop={false}
                            horizontalCenter={true}
                            open={this.tooltipOpen}
                            verticalOffset={10}
                        >
                            {
                                (setPopoverRef, styles, verticalPosition) => (
                                    <span
                                        aria-hidden={true}
                                        className={classNames(tooltipStyles.tooltip, tooltipStyles[verticalPosition])}
                                        ref={setPopoverRef}
                                        style={styles}
                                    >
                                        {label}
                                    </span>
                                )
                            }
                        </Popover>
                }

                {children}
            </span>
        );
    }
}

export default Tooltip;
