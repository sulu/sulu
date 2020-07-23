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
    selected: boolean,
    setWidth?: (index: ?number, width: number) => void,
    small: boolean,
};

const DEBOUNCE_TIME = 200;

@observer
class Tab extends React.Component<Props> {
    @observable selected = false;

    static defaultProps = {
        selected: false,
        small: false,
    };

    listItemRef: ?ElementRef<'li'>;
    resizeObserver: ?ResizeObserver;

    @action componentDidMount() {
        const {index, setWidth, selected} = this.props;

        this.resizeObserver = new ResizeObserver(
            debounce(this.setWidth, DEBOUNCE_TIME)
        );

        if (!this.listItemRef) {
            return;
        }

        this.resizeObserver.observe(this.listItemRef);

        if (setWidth && this.listItemRef) {
            setWidth(index, this.listItemRef.offsetWidth);
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

    setWidth = ([entry]: ResizeObserverEntry[]) => {
        const {index, setWidth} = this.props;

        if (setWidth && entry) {
            setWidth(index, entry.contentRect.width);
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
