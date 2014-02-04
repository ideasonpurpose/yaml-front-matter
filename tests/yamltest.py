#!/usr/bin/env python
"""This needs to be cleaned up and properly setup so it can run as a module too




"""
import os
import yaml
from time import time
from termcolor import colored

start_time = time()
total, errors, passed = 0, 0, 0

yamlroot = os.path.realpath('../../content')
for root, subdirs, files in os.walk(yamlroot):
    for f in files:
        filepath = os.path.join(root, f)
        if os.path.splitext(f)[-1].lower() in ['.yaml', '.yml']:
            total = total + 1
            with open(filepath) as thefile:

                try:
                    yaml.load(thefile)
                    passed = passed + 1
                except yaml.YAMLError, err:
                    mark = err.problem_mark
                    thefile.seek(0)
                    flines = thefile.readlines()
                    line_num = "Line " + "{0}: ".format(mark.line + 1).rjust(len(str(len(flines))) + 2, ' ')
                    err_line = flines[mark.line]
                    if err.context is not None:
                        err.problem = ' '.join([err.context, err.problem])
                    print "Error parsing {0}".format(colored(os.path.relpath(filepath, yamlroot), attrs=["underline"]))
                    print colored(err.problem, 'yellow')

                    print colored(line_num, attrs=['bold']),
                    print "{0}{1}{2}".format(
                                    err_line[:mark.column],
                                    colored(err_line[mark.column], 'white', 'on_red', attrs=['bold']),
                                    err_line[mark.column + 1:]),
                    print '^\n'.rjust(mark.column + len(line_num) + 2)
                    errors = errors + 1

if errors is 0:
    print "\nCongratulations, no errors found:"
print "{0} YAML files checked, {1} passed, {2} errors.\n".format(
                                                    colored(total, 'white'),
                                                    colored(passed, 'green'),
                                                    colored(errors, 'red'))
print "Total processing time: {:.3f} seconds.".format(time() - start_time)
