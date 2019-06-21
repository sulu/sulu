// @flow

type Condition = {||};

export type Rule = {|
    conditions: Array<Condition>,
    frequency: number,
    title: string,
|};
