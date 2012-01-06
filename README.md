# Repo Scope

This is a simple PHP tool intended to help developers collect code repository
data for use in their projects, the better to provide enriched debugging
information.

I found I kept piecing together the bits most relevant to GitScope, so putting
together a single solution will help simplify several other projects.

 * A few common data points, shell command reasonably worked out.
 * Carefully structured static caching and shell command minimization, to 
   minimize repeat visits to the shell.
 * A change to collaborate on one solution, instead of doing the same 
   bits-and-pieces I kept facing.

## Using Repo Scope

You can use the RepoScope object if you like, it is primarily useful for it's
cmd() command, which you can use to statically cache the results of a shell
command.

RepoScope ships with one fully-featured implementation: GitScope.

## Homepage
http://github.com/grayside/reposcope
