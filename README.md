# KoolKode BPMN 2.0 Process Engine

[![Build Status](https://travis-ci.org/koolkode/bpmn.svg?branch=master)](https://travis-ci.org/koolkode/bpmn)

**This project is not ready for production usage yet.**

Provides a basic process engine that can load BPMN 2.0 diagrams and execute contained processes. The BPMN engine
requires a relational database that is supported by KoolKode Database in order to persist process definitions, instances
and other runtime data. Like [Activiti](http://activiti.org/) it makes good use of the command pattern during execution
of a process instance.

I recommend [camunda Modeler](http://camunda.org/bpmn/tool/) to create executable process / collaboration diagrams. There is
no need for a graphical editor, but it turns creating process definitions into a more pleasant experience. The engine ships
with a class called `BusinessProcessBuilder` that can be used to create a process without loading it from a BPMN 2.0
XML file.

## Supported BPMN 2.0 Elements

As of now KoolKode BPMN supports only a limited sub-set of BPMN elements.

### Gateways

- Exclusive
- Inclusive
- Parallel
- Event-based (exclusive)

### Activities

- Task
- Manual Task
- Human Task
- Service Task
- Script Task (PHP only)
- Send Task
- Receive Task
- Subprocess
- Call Activity
- Event Subprocess

### Events

- **None** - Start / Intermediate / End
- **Link** - Intermediate
- **Terminate** - End
- **Signal** - Start / Intermediate / Boundary / End
- **Messafe** - Start / Intermediate / Boundary / End
- **Timer** - Intermediate
