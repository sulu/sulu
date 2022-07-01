// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import stickyStyles from './sticky.scss';
import type {ElementRef, Node} from 'react';

type Props = {|
    children: (
        isSticky: boolean,
    ) => Node,
    top: number,
|};

@observer
class Sticky extends React.Component<Props> {
    static defaultProps = {
        top: 0,
    };

    intersectionObserver: ?IntersectionObserver;

    constructor(props: Props) {
        super(props);

        if (typeof IntersectionObserver !== 'undefined') {
            this.intersectionObserver = new IntersectionObserver((records) => {
                for (const record of records) {
                    action(() => {
                        this.isSticky = !record.isIntersecting;
                    })();
                }
            }, {});
        }
    }

    componentWillUnmount() {
        if (this.intersectionObserver) {
            this.intersectionObserver.disconnect();
        }
    }

    @observable isSticky: boolean = false;

    @observable stickySentinelRef: ElementRef<*>;

    setStickySentinelRef = (ref: ElementRef<*>) => {
        this.stickySentinelRef = ref;

        if (!this.stickySentinelRef || !this.intersectionObserver) {
            return;
        }

        this.intersectionObserver.observe(this.stickySentinelRef);
    };

    render() {
        const {
            children,
            top,
        } = this.props;

        return (
            <>
                <div
                    className={stickyStyles.stickySentinel}
                    ref={this.setStickySentinelRef}
                    style={{top: (0 - top - 1)}}
                />

                <div className={stickyStyles.sticky} style={{top}}>
                    {children(this.isSticky)}
                </div>
            </>
        );
    }
}

export default Sticky;
