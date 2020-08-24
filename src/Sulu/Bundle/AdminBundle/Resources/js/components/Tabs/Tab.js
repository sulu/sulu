// @flow
import React from 'react';
import type {ElementRef} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import debounce from 'debounce';
import tabStyles from './tab.scss';

type Props = {
    children: string,
    index?: number,
    onClick?: (index: ?number) => void,
    onWidthChange?: (index: ?number, width: number) => void,
    selected: boolean,
    small: boolean,
};

const DEBOUNCE_TIME = 200;

@observer
class Tab extends React.Component<Props> {
    static defaultProps = {
        selected: false,
        small: false,
    };

    @observable selected = false;

    listItemRef: ?ElementRef<'li'>;
    resizeObserver: ?ResizeObserver;

    @action componentDidMount() {
        const {index, onWidthChange, selected} = this.props;

        this.resizeObserver = new ResizeObserver(
            debounce(this.handleWidthChange, DEBOUNCE_TIME)
        );

        if (!this.listItemRef) {
            return;
        }

        this.resizeObserver.observe(this.listItemRef);

        if (onWidthChange && this.listItemRef) {
            onWidthChange(index, this.listItemRef.offsetWidth);
        }

        if (selected) {
            this.selected = true;
        }
    }

    componentWillUnmount() {
        if (this.resizeObserver) {
            this.resizeObserver.disconnect();
        }
    }

    @action componentDidUpdate(prevProps: Props) {
        const {selected: prevSelected} = prevProps;
        const {selected} = this.props;

        if (prevSelected !== selected) {
            this.selected = selected;
        }
    }

    setListItemRef = (ref: ?ElementRef<'li'>) => {
        this.listItemRef = ref;
    };

    handleWidthChange = ([entry]: ResizeObserverEntry[]) => {
        const {index, onWidthChange} = this.props;

        if (onWidthChange && entry) {
            onWidthChange(index, entry.contentRect.width);
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
            small,
            selected,
        } = this.props;

        const tabClass = classNames(
            tabStyles.tab,
            {
                [tabStyles.selected]: this.selected,
                [tabStyles.small]: small,
            }
        );

        return (
            <li className={tabClass} ref={this.setListItemRef}>
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
