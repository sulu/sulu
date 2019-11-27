// @flow
import React from 'react';
import type {ElementRef} from 'react';
import Dropzone from 'react-dropzone';
import Button from '../Button';
import type {ButtonSkin} from '../Button';

type Props = {|
    children: string,
    disabled: boolean,
    icon: string | typeof undefined,
    onUpload: (file: File) => void,
    skin: ButtonSkin | typeof undefined,
|};

export default class FileUploadButton extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
        icon: undefined,
        skin: undefined,
    };

    dropzoneRef: ElementRef<typeof Dropzone>;

    setDropzoneRef = (ref: ElementRef<typeof Dropzone>) => {
        this.dropzoneRef = ref;
    };

    handleDrop = (files: Array<File>) => {
        const file = files[0];

        this.props.onUpload(file);
    };

    render() {
        const {children, disabled, icon, skin} = this.props;

        return (
            <Dropzone
                onDrop={this.handleDrop}
                ref={this.setDropzoneRef}
                style={{}}
            >
                <Button disabled={disabled} icon={icon} skin={skin}>
                    {children}
                </Button>
            </Dropzone>
        );
    }
}
