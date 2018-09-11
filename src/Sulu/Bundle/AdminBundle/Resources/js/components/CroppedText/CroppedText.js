// @flow
import React from 'react';
import croppedTextStyle from './croppedText.scss';

type Props = {
    children: ?(string | number),
};

export default class CroppedText extends React.PureComponent<Props> {
    render() {
        let {children} = this.props;

        if (!children) {
            return null;
        }

        children = children.toString();

        const index = Math.ceil(children.length / 2);
        const frontText = children.substr(0, index);
        const backText = children.substr(index);

        return (
            <div
                aria-label={children}
                className={croppedTextStyle.croppedText}
                title={children}
            >
                <div aria-hidden={true} className={croppedTextStyle.front}>{frontText}</div>
                <div aria-hidden={true} className={croppedTextStyle.back}><span>{backText}</span></div>
                <div className={croppedTextStyle.whole}>{children}</div>
            </div>
        );
    }
}
