// @flow
import React from 'react';
import type {ElementRef} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import tabStyles from './tab.scss';

type Props = {
    children: string,
    hidden: boolean,
    index?: number,
    onClick?: (index: ?number) => void,
    onRefChange?: (index: ?number, ref: ?ElementRef<'li'>) => void,
    selected: boolean,
    small: boolean,
};

@observer
class Tab extends React.Component<Props> {
    static defaultProps = {
        hidden: false,
        selected: false,
        small: false,
    };

    @observable selected = false;

    @action componentDidMount() {
        const {selected} = this.props;

        if (selected) {
            this.selected = true;
        }
    }

    @action componentDidUpdate(prevProps: Props) {
        const {selected: prevSelected} = prevProps;
        const {selected} = this.props;

        if (prevSelected !== selected) {
            this.selected = selected;
        }
    }

    handleRefChange = (ref: ?ElementRef<'li'>) => {
        const {index, onRefChange} = this.props;

        if (onRefChange) {
            onRefChange(index, ref);
        }
    };

    handleClick = () => {
        const {index, onClick} = this.props;

        if (onClick) {
            onClick(index);
        }
    };

    render() {
        const {
            children,
            hidden,
            small,
            selected,
        } = this.props;

        const tabClass = classNames(
            tabStyles.tab,
            {
                [tabStyles.hidden]: hidden,
                [tabStyles.selected]: this.selected,
                [tabStyles.small]: small,
            }
        );

        return (
            <li className={tabClass} ref={this.handleRefChange}>
                <button
                    disabled={selected}
                    onClick={this.handleClick}
                    title={children}
                >
                    {children}
                </button>
            </li>
        );
    }
}

export default Tab;
