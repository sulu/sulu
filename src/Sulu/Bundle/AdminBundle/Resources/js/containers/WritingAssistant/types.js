// @flow

export type MessageType = {|
    collapsed: boolean,
    command?: string,
    displayActions?: boolean,
    expert?: string,
    text: string,
    title?: string,
    type: 'text_line' | 'text_area' | 'text_editor',
|};

export type RequestErrorType = {|
    messageKey: ?string,
    prompt: string,
    title: ?string,
|};

export type ExpertType = {|
    description?: string,
    name: string,
    options: {
        predefinedPrompts?: Array<{
            icon?: ?string,
            name: string,
            prompt: string,
        }>,
    },
    uuid: string,
|};
