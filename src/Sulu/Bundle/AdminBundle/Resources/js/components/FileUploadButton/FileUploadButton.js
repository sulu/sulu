// @flow
import React from 'react';
import Dropzone from 'react-dropzone';
import Button from '../Button';
import type {Node} from 'react';
import type {ButtonSkin} from '../Button';

type Props = {|
    children?: Node,
    disabled: boolean,
    icon: string | typeof undefined,
    onUpload: (file: File) => void,
    skin: ButtonSkin | typeof undefined,
    accept: string | typeof undefined,
|};

export default class FileUploadButton extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
        icon: undefined,
        skin: undefined,
        accept: undefined,
    };

    handleDrop = (files: Array<File>) => {
        const file = files[0];

        this.props.onUpload(file);
    };

    render() {
        const {children, disabled, icon, skin, accept} = this.props;
        
        return (
            <Dropzone
                onDrop={this.handleDrop}
                style={{}}
            >
                {({getInputProps, getRootProps}) => (
                    <div {...getRootProps()}>
                        <Button disabled={disabled} icon={icon} skin={skin}>
                            {children}
                        </Button>
                        <input {...getInputProps()} accept={accept} />
                    </div>
                )}
            </Dropzone>
        );
    }
}
