// @flow

export type Condition = {|
    condition: Object,
    type: ?string,
|};

export type Rule = {|
    conditions: Array<Condition>,
    frequency: number,
    title: string,
|};

export type RuleType = {
    name: string,
    type: {|
        name: string,
        options: Object,
    |},
};

export type RuleTypes = {[key: string]: RuleType};

export type RuleTypeProps = {|
    onChange: (value: Object) => void,
    options: Object,
    value: Object,
|};
