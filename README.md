# KoolKode BPMN 2.0 Process Engine

[![Build Status](https://travis-ci.org/koolkode/bpmn.svg?branch=master)](https://travis-ci.org/koolkode/bpmn)

Provides a basic process engine that can load BPMN 2.0 diagrams and execute contained processes. The BPMN engine
requires a relational database that is supported by KoolKode Database in order to persist process definitions, instances
and other runtime data. Like [Activiti](http://activiti.org/) it makes good use of the command pattern during execution
of a process instance.

The engine is currently missing support for timer events due to PHP's lack of a native
background job scheduling feature. Async continuations before and after activities are supported but
require a job scheduler (cron, message queue, etc.) that can execute PHP jobs. The engine will fall
back to synchronous continuations by default if the job executor is not configured for background tasks.
