#! /usr/bin/perl
#
#  svncopy.pl  --  Utility script for copying with branching/tagging.
#
#  This program is free software; you can redistribute  it and/or modify it
#  under  the terms of  the GNU General  Public License as published by the
#  Free Software Foundation;  either version 2 of the  License, or (at your
#  option) any later version.
#
#  THIS  SOFTWARE  IS PROVIDED   ``AS  IS'' AND   ANY  EXPRESS  OR  IMPLIED
#  WARRANTIES,   INCLUDING, BUT NOT  LIMITED  TO, THE IMPLIED WARRANTIES OF
#  MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.  IN
#  NO  EVENT  SHALL   THE AUTHOR  BE    LIABLE FOR ANY   DIRECT,  INDIRECT,
#  INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
#  NOT LIMITED   TO, PROCUREMENT OF  SUBSTITUTE GOODS  OR SERVICES; LOSS OF
#  USE, DATA,  OR PROFITS; OR  BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
#  ANY THEORY OF LIABILITY, WHETHER IN  CONTRACT, STRICT LIABILITY, OR TORT
#  (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
#  THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE. 
#
#  You should have received a copy of the  GNU General Public License along
#  with this program; if not, write  to the Free Software Foundation, Inc.,
#  59 Temple Place - Suite 330, Boston MA 02111-1307 USA.
#
#  This product makes use of software developed by 
#  CollabNet (http://www.Collab.Net/), see http://subversion.tigris.org/.
#
#  This software consists of voluntary contributions made by many
#  individuals.  For exact contribution history, see the revision
#  history and logs, available at http://subversion.tigris.org/.
#------------------------------------------------------------------------------

#------------------------------------------------------------------------------
#
#  This script copies one Subversion location to another, in the same way as
#  svn copy.  Using the script allows proper branching and tagging of URLs
#  containing svn:externals definitions.
#
#  For more information see the pod documentation at the foot of the file,
#  or run svncopy.pl -?.
#
#------------------------------------------------------------------------------

#
# Include files
#
use Cwd;
use File::Temp   0.12   qw(tempdir tempfile);
use Getopt::Long 2.25;
use Pod::Usage;
use URI          1.17;

#
# Global definitions
#

# Specify the location of the svn command.
#my $svn = '@SVN_BINDIR@/svn';
my $svn = 'svn';

# Input parameters
my $testscript = 0;
my $verbose = 0;
my $pin_externals = 0;
my $update_externals = 0;
my @sources;
my $destination;
my $message;
my @svn_options = ();

# Internal information
my %externals_hash;
my $temp_dir;

# Error handling
my @errors = ();
my @warnings = ();

# Testing-specific variables
my $hideerrors = 0;


#------------------------------------------------------------------------------
# Main execution block
#

#
# Process arguments
#
GetOptions( "pin-externals|tag|t" => \$pin_externals,
            "update-externals|branch|b" => \$update_externals,
            "message|m=s" => \$message,
            "revision|r=s" => \$revision,
            "verbose!" => \$verbose,
            "quiet|q" => sub { $verbose = 0; push( @svn_options, "--quiet" ) },
            "file|F=s" => sub { push( @svn_options, "--file", $_[1] ) },
            "username=s" => sub { push( @svn_options, "--username", $_[1] ) },
            "password=s" => sub { push( @svn_options, "--password", $_[1] ) },
            "no_auth_cache" => sub { push( @svn_options, "--no-auth-cache" ) },
            "force-log" => sub { push( @svn_options, "--force-log" ) },
            "encoding=s" => sub { push( @svn_options, "--encoding", $_[1] ) },
            "config-dir=s" => sub { push( @svn_options, "--config-dir", $_[1] ) },
            "help|?" => sub{ Usage() },
            ) or Usage();

# Put in a signal handler to clean up any temporary directories.
sub catch_signal {
  my $signal = shift;
  warn "$0: caught signal $signal.  Quitting now.\n";
  exit 1;
}

$SIG{HUP}  = \&catch_signal;
$SIG{INT}  = \&catch_signal;
$SIG{TERM} = \&catch_signal;
$SIG{PIPE} = \&catch_signal;

#
# Check our parameters
#
if ( @ARGV < 2 )
{
  Usage( "Please specify source and destination" );
  exit 1;
}

#
# Get source(s) and destination.
#
push ( @sources, shift( @ARGV ) );
$destination = shift( @ARGV );
while ( scalar( @ARGV ) )
  {
    push( @sources, $destination );
    $destination = shift( @ARGV );
  }

#
# Any validation errors?  If so, bomb out.
#
if ( scalar( @errors ) > 0 )
  {
    print "\n", @errors;
    Usage();
    exit scalar( @errors );
  }

#
# Now do the main processing.
# This will update @errors if anything goes wrong.
#
if ( !DoCopy( \@sources, $destination, $message ) )
  {
    print "\n*****************************************************************\n";
    print "Errors:\n";
    print @errors;
  }

exit scalar( @errors );


#------------------------------------------------------------------------------
# Function:    DoCopy
#
# Does the work of the copy.
#
# Parameters:
#       sources         Reference to array containing source URLs
#       destination     Destination URL
#       message         Commit message to use
#
# Returns:     0 on error
#
# Updates @errors.
#------------------------------------------------------------------------------
sub DoCopy
{
  my ( $sourceref, $destination, $message ) = @_;
  my @sources = @$sourceref;
  my $revstr = "";
  my $src;
  my $startdir = cwd;
  my $starterrors = scalar( @errors );
    
  print "\n=================================================================\n";
  $revstr = "\@$revision" if $revision;
  print "=== Copying from:\n";
  foreach $src ( @sources ) { print "===       $src$revstr\n"; }
  print "===\n";
  print "=== Copying to:\n";
  print "===       $destination\n";
  print "===\n";
  print "===  - branching (updating fully-contained svn:externals definitions)\n" if $update_externals;
  if ( $pin_externals )
    {
      my $revtext = $revision ? "revision $revision" : "current revision";
      print "===  - tagging (pinning all svn:externals definitions to $revtext)\n";
    }
  print "===\n" if ( $update_externals or $pin_externals );

  # Convert destination to URI
  $destination =~ s|/*$||;
  my $destination_uri = URI->new($destination);
   
  #
  # Generate a message if we don't have one.
  #
  unless ( $message )
    {
      $message = "svncopy.pl: Copied to '$destination'\n";
      foreach $src ( @sources )
        {
          $message .= "            Copied from '$src'\n";
        }
    }
    
  #
  # Create a temporary directory to work in.
  #
  my ( $auto_temp_dir, $dest_dir ) =
    PrepareDirectory( $destination_uri, "svncopy.pl to '$destination'\n - creating intermediate directory" );
  $temp_dir = $auto_temp_dir->temp_dir();
  chdir( $temp_dir );
    
  foreach $src ( @sources )
    {
      # Convert source to URI
      $src =~ s|/*$||;
      my $source_uri = URI->new($src);

      #
      # Do the initial copy into our temporary.  Note this will create a
      # subdirectory with the same name as the last directory in $source.
      #
      if ( !CopyToWorkDir( $src, $dest_dir ) )
        {
          error( "Copy failed" );
          return 0;
        }
    }
        
  #
  # Do any processing.
  #
  if ( $pin_externals or $update_externals )
    {
      if ( !UpdateExternals( $sourceref, $destination, $dest_dir, \$message ) )
        {
          error( "Couldn't update svn:externals" );
          return 0;
        }
    }
    
  #
  # And check in the new.
  #
  DoCommit( $dest_dir, $message ) or die "Couldn't commit\n";
    
  # Make sure we finish in the directory we started
  chdir( $startdir );

  print "=== ... copy complete\n";
  print "=================================================================\n";
    
  # Return whether there was an error.
  return ( scalar( @errors ) == $starterrors );
}


#------------------------------------------------------------------------------
# Function:    PrepareDirectory
#
# Prepares a temporary directory to work in.
#
# Parameters:
#       destination    Destination URI
#       message        Commit message
#
# Returns:     temporary directory and subdirectory to work in
#------------------------------------------------------------------------------
sub PrepareDirectory
{
  my ( $destination, $message ) = @_;

  my $auto_temp_dir = Temp::Delete->new();
  $temp_dir = $auto_temp_dir->temp_dir();
  info( "Using temporary directory $temp_dir\n" );
  
  #
  # Our working destination directory has the same name as the last directory
  # in the destination URI.
  #
  my @path_segments = grep { length($_) } $destination->path_segments;
  my $new_dir = pop( @path_segments );
  my $dest_dir = "$temp_dir/$new_dir";
  
  # Make sure the destination directory exists in Subversion.
  info( "Creating intermediate directories (if necessary)\n" );
  if ( !CreateSVNDirectories( $destination, $message ) )
    {
      error( "Couldn't create parent directories for '$destination'" );
      return;
    }
  
  # Check out the destination.
  info( "Checking out destination directory '$destination'\n" );
  if ( 0 != SVNCall( 'co', $destination, $dest_dir ) )
    {
      error( "Couldn't check out '$destination' into work directory." );
      return;
    }
  
  return ( $auto_temp_dir, $dest_dir );
}


#------------------------------------------------------------------------------
# Function:    CopyToWorkDir
#
# Does the svn copy into the temporary directory.
#
# Parameters:
#       source      The URI to copy from
#       work_dir    The working directory
#
# Returns:     1 on success
#------------------------------------------------------------------------------
sub CopyToWorkDir
{
  my ( $source, $work_dir ) = @_;
  my $dest_dir = DestinationSubdir( $source, $work_dir );
  my @commandline = ();
    
  push( @commandline, "--revision", $revision ) if ( $revision );
    
  push( @commandline, $source, $work_dir );
        
  my $exit = SVNCall( "copy", @commandline );
    
  error( "$0: svn copy failed" ) if ( 0 != $exit );
    
  return ( 0 == $exit );
}


#------------------------------------------------------------------------------
# Function:    DestinationSubdir
#
# Returns the destination directory for a given source and a destination root
# directory.
#
# Parameters:
#       source      The URL to copy from
#       destination The working directory
#
# Returns:     The relevant directory
#------------------------------------------------------------------------------
sub DestinationSubdir
{
  my ( $source, $destination ) = @_;
  my $subdir;
    
  # Make sure source and destination are consistent about separator.
  # Note every function we call can handle Unix path format, so we
  # default to that.
  $source =~ s|\\|/|g;
  $destination =~ s|\\|/|g;

  # Find the last directory - that's the subdir we'll use in $destination
  if ( $source =~ m"/([^/]+)/*$" )
    {
      $subdir = $1;
    }
    else
    {
      $subdir = $source;
    }
  return "$destination/$subdir";
}


#------------------------------------------------------------------------------
# Function:    UpdateExternals
#
# Updates the svn:externals in the tree according to the --pin-externals or
# --update_externals options.
#
# Parameters:
#       sourceref   Ref to the URLs to copy from
#       destination The URL being copied to
#       work_dir    The working directory
#       msgref      Ref to message string to update with changes
#
# Returns:     1 on success
#------------------------------------------------------------------------------
sub UpdateExternals
{
  my ( $sourceref, $destination, $work_dir, $msgref ) = @_;
  my @commandline = ();
  my $msg;
  my @dirfiles;
  my %extlist;

  # Check the externals on this directory and subdirectories
  info( "Checking '$work_dir'\n" );
  %extlist = GetRecursiveExternals( $work_dir );
    
  # And do the update
  while ( my ( $subdir, $exts ) = each ( %extlist ) )
    {
      my @externals = @$exts;
      if ( scalar( @externals ) )
        {
          UpdateExternalsOnDir( $sourceref, $destination, $subdir, $msgref, \@externals );
        }
    }
  
  return 1;
}


#------------------------------------------------------------------------------
# Function:    UpdateExternalsOnDir
#
# Updates the svn:externals in the tree according to the --pin-externals or
# --update_externals options.
#
# Parameters:
#       sourceref   Ref to the URLs to copy from
#       destination The URL being copied to
#       work_dir    The working directory
#       externals   Ref to the externals on the directory
#       msgref      Ref to message string to update with changes
#
# Returns:     1 on success
#------------------------------------------------------------------------------
sub UpdateExternalsOnDir
{
  my ( $sourceref, $destination, $work_dir, $msgref, $externalsref ) = @_;
  my @sources = @$sourceref;
  my @externals = @$externalsref;
  my @new_externals;
  my %changed;
    
  # Do any updating required
  foreach my $external ( @externals )
    {
      chomp( $external );
      next unless ( $external =~ m"^(\S+)(\s+)(?:-r\s*(\d+)\s+)?(.*)" );
      my ( $ext_dir, $spacing, $ext_rev, $ext_val ) = ( $1, $2, $3, $4 );
            
      info( " - Found $ext_dir => '$ext_val'" );
      info( " ($ext_rev)" ) if $ext_rev;
      info( "\n" );
        
      $externals_hash{ "$ext_val" } = $ext_rev;
        
      # Only update if it's not pinned to a version
      if ( !$ext_rev )
        {
          if ( $update_externals )
            {
              my $old_external = $external;
              foreach my $source ( @sources )
                {
                  my $dest_dir = DestinationSubdir( $source, $destination );
                  #info( "Checking against '$source'\n" );
                  if ( $ext_val =~ s|^$source|$dest_dir| )
                    {
                      $external = "$ext_dir$spacing$ext_val";
                      info( " - updated '$old_external' to '$external'\n" );
                      $changed{$old_external} = $external;
                    }
                }
            }
          elsif ( $pin_externals )
            {
              # Find the last revision of the destination and pin to that.
              my $old_external = $external;
              my $rev = LatestRevision( $ext_val, $revision );
              #info( "Pinning '$ext_val' to '$rev'\n" );
              $external = "$ext_dir -r $rev$spacing$ext_val";
              info( " - updated '$old_external' to '$external'\n" );
              $changed{$old_external} = $external;
            }
        }
      push( @new_externals, $external );
    }

  # And write the changes back
  if ( scalar( %changed ) )
    {
      # Update the commit log message
      my %info = SVNInfo( $work_dir );
      $$msgref .= "\n * $info{URL}: update svn:externals\n";
      while ( my ( $old, $new ) = each( %changed ) )
        {
          $$msgref .= "   from '$old' to '$new'\n";
          info( "   '$old' => '$new'\n" );
        }

      # And set the new externals
      my ($handle, $tmpfile) = tempfile( DIR => $temp_dir );
      print $handle join( "\n", @new_externals );
      close($handle);
      SVNCall( "propset", "--file", $tmpfile, "svn:externals", $work_dir );
    }
}


#------------------------------------------------------------------------------
# Function:    GetRecursiveExternals
#
# This function retrieves the svn:externals value from the
# specified URL or location and subdirectories.
#
# Parameters:
#       location      location of SVN object - file/dir or URL.
#
# Returns:     hash
#------------------------------------------------------------------------------
sub GetRecursiveExternals
{
  my ( $location ) = @_;
  my %retval;
  my $externals;
  my $subdir = ".";
    
  my ( $status, @externals ) = SVNCall( "propget", "-R", "svn:externals", $location );
    
  foreach my $external ( @externals )
    {
      chomp( $external );

      if ( $external =~ m"(.*) - (.*\s.*)" )
        {
          $subdir = $1;
          $external = $2;
        }

      push( @{$retval{$subdir}}, $external ) unless $external =~ m"^\s*$";
    }

  return %retval;
}


#------------------------------------------------------------------------------
# Function:    SVNInfo
#
# Gets the info about the given file.
#
# Parameters:
#       file    The SVN object to query
#
# Returns:     hash with the info
#------------------------------------------------------------------------------
sub SVNInfo
{
  my $file = shift;
  my $old_verbose = $verbose;
  $verbose = 0;
  my ( $retval, @output ) = SVNCall( "info", $file );
  $verbose = $old_verbose;
  my %info;
    
  return if ( 0 != $retval );
    
  foreach my $line ( @output )
    {
      if ( $line =~ "^(.*): (.*)" )
        {
          $info{ $1 } = $2;
        }
    }
    
  return %info;
}


#------------------------------------------------------------------------------
# Function:    LatestRevision
#
# Returns the repository revision of the last change to the given object not
# later than the given revision (i.e. it may return revision, but won't
# return revision+1).
#
# Parameters:
#       source      The URL to check
#       revision    The revision of the URL to check from (if not supplied
#                   defaults to last revision).
#
# Returns:     The relevant revision number
#------------------------------------------------------------------------------
sub LatestRevision
{
  my ( $source, $revision ) = @_;
  my $revtext = "";

  if ( $revision )
    {
      $revtext = "--revision $revision:0";
    }
        
  my $old_verbose = $verbose;
  $verbose = 0;
  my ( $retval, @output ) = SVNCall( "log -q", $revtext, $source );
  $verbose = $old_verbose;

  if ( 0 != $retval )
    {
      error( "LatestRevision: log -q on '$source' failed" );
      return -1;
    }
    
  #
  # The second line should give us the info we need: e.g.
  #
  # >svn log -q http://subversion/svn/scratch/ianb/svncopy-update/source/dirA
  # ------------------------------------------------------------------------
  # r1429 | ib | 2004-06-14 17:39:36 +0100 (Mon, 14 Jun 2004)
  # ------------------------------------------------------------------------
  # r1423 | ib | 2004-06-14 17:39:26 +0100 (Mon, 14 Jun 2004)
  # ------------------------------------------------------------------------
  # r1422 | ib | 2004-06-14 17:39:23 +0100 (Mon, 14 Jun 2004)
  # ------------------------------------------------------------------------
  # r1421 | ib | 2004-06-14 17:39:22 +0100 (Mon, 14 Jun 2004)
  # ------------------------------------------------------------------------
  #
  # The second line starts with the latest revision number.
  #
  if ( $output[1] =~ m"^r(\d+) \|" )
    {
      return $1;
    }
    
  error( "LatestRevision: log output not formatted as expected\n" );
    
  return -1;
}


#------------------------------------------------------------------------------
# Function:    DoCommit
#
# svn commits the temporary directory.
#
# Parameters:
#       work_dir    The working directory
#       message     Commit message
#
# Returns:     non-zero on success
#------------------------------------------------------------------------------
sub DoCommit
{
  my ( $work_dir, $message ) = @_;
  my @commandline = ();
    
  # Prepare a file containing the message
  my ($handle, $messagefile) = tempfile( DIR => $temp_dir );
  print $handle $message;
  close($handle);
  push( @commandline, "--file", $messagefile );
    
  push( @commandline, $work_dir );
        
  my ( $exit ) = SVNCall( "commit", @commandline );
    
  error( "$0: svn commit failed" ) if ( 0 != $exit );
    
  return ( 0 == $exit );
}


#------------------------------------------------------------------------------
# Function:    SVNCall
#
# Makes a call to subversion.
#
# Parameters:
#       command     Subversion command
#       options     Other options to pass to Subversion
#
# Returns:     exit status, output from command
#------------------------------------------------------------------------------
sub SVNCall
{
  my ( $command, @options ) = @_;

  my @commandline = ( $svn, $command, @svn_options, @options );

  info( " > ", join( " ", @commandline ), "\n" );
  
  my @output = qx( @commandline 2>&1 );
      
  my $result = $?;
  my $exit   = $result >> 8;
  my $signal = $result & 127;
  my $cd     = $result & 128 ? "with core dump" : "";
  if ($signal or $cd)
    {
      error( "$0: 'svn $command' failed $cd: exit=$exit signal=$signal\n" );
    }
  
  if ( $exit > 0 )
    {
      info( join( "\n", @output ) );
    }
  if ( wantarray )
    {
      return ( $exit, @output );
    }
    
  return $exit;
}


#------------------------------------------------------------------------------
# Function:    FindRepositoryRoot
#
# Returns the root of the repository for a given URL.  Do
# this with the svn log command.  Take the svn_url hostname and port
# as the initial url and append to it successive portions of the final
# path until svn log succeeds.
#
# Parameters:
#       URI    URI within repository
#
# Returns:     A URI for the root, or undefined on error
#------------------------------------------------------------------------------
sub FindRepositoryRoot
{
  my $URI = shift;
  my $repos_root_uri;
  my $repos_root_uri_path;
  my $old_verbose = $verbose;
  $verbose = 0;
  
  info( "Finding the root URL of '$URI'.\n" );
  
  my $r = $URI->clone;
  my @path_segments = grep { length($_) } $r->path_segments;
  unshift(@path_segments, '');
  $r->path('');
  my @r_path_segments;

  while (@path_segments)
    {
      $repos_root_uri_path = shift @path_segments;
      push(@r_path_segments, $repos_root_uri_path);
      $r->path_segments(@r_path_segments);
      if ( SVNCall( 'log', '-r', 'HEAD', $r ) == 0 )
        {
          $repos_root_uri = $r;
          last;
        }
    }
    
  $verbose = $old_verbose;
  
  if ($repos_root_uri)
    {
      info( "Determined that the svn root URL is $repos_root_uri.\n\n" );
      return $repos_root_uri;
    }
  else
    {
      error( "$0: cannot determine root svn URL for '$URI'.\n" );
      return;
    }
}


#------------------------------------------------------------------------------
# Function:    CreateSVNDirectories
#
# Creates a directory in Subversion, including all intermediate directories.
#
# Parameters:
#       URI         directory path to create.
#       message     commit message (optional).
#
# Returns:     1 on success, 0 on error
#------------------------------------------------------------------------------
sub CreateSVNDirectories
{
  my ( $URI, $message ) = @_;
  my $r = $URI->clone;
  my @path_segments = grep { length($_) } $r->path_segments;
  my @r_path_segments;
  unshift(@path_segments, '');
  $r->path('');

  my $found_root = 0;
  my $found_tail = 0;

  # Prepare a file containing the message
  my ($handle, $messagefile) = tempfile( DIR => $temp_dir );
  print $handle $message;
  close($handle);
  my @msgcmd = ( "--file", $messagefile );

  # We're going to get errors while we do this.  Don't show the user.
  my $old_verbose = $verbose;
  $verbose = 0;
  # Find the repository root
  while (@path_segments)
    {
      my $segment = shift @path_segments;
      push( @r_path_segments, $segment );
      $r->path_segments( @r_path_segments );
      if ( !$found_root )
        {
          if ( SVNCall( 'log', '-r', 'HEAD', $r ) == 0 )
            {
              # We've found the root of the repository.
              $found_root = 1;
            }
        }
      elsif ( !$found_tail )
        {
          if ( SVNCall( 'log', '-r', 'HEAD', $r ) != 0 )
            {
              # We've found the first directory which doesn't exist.
              $found_tail = 1;
            }
        }
        
      if ( $found_tail )
        {
          # We're creating directories
          $verbose = $old_verbose;
          if ( 0 != SVNCall( 'mkdir', @msgcmd, $r ) )
            {
              error( "Couldn't create directory '$r'" );
              return 0;
            }
        }
    }
  $verbose = $old_verbose;
  
  return 1;
}


#------------------------------------------------------------------------------
# Function:    info
#
# Prints out an informational message in verbose mode
#
# Parameters:
#       @_     The message(s) to print
#
# Returns:     none
#------------------------------------------------------------------------------
sub info
{
  if ( $verbose )
    {
      print @_;
    }
}


#------------------------------------------------------------------------------
# Function:    error
#
# Prints out and logs an error message
#
# Parameters:
#       @_     The error messages
#
# Returns:     none
#------------------------------------------------------------------------------
sub error
{
  my $error;
    
  # This is used during testing
  if ( $hideerrors )
    {
      return;
    }
    
  # Now print out each error message and add it to the list.
  foreach $error ( @_ )
    {
      my $text = "svncopy.pl: $error\n";
      push( @errors, $text );
      if ( $verbose )
        {
          print $text;
        }
    }
}


#------------------------------------------------------------------------------
# Function:    Usage
#
# Prints out usage information.
#
# Parameters:
#       optional error message
#
# Returns:     none
#------------------------------------------------------------------------------
sub Usage
{
  my $msg;
  $msg = "\n*** $_[0] ***\n" if $_[0];
    
  pod2usage( { -message => $msg,
               -verbose => 0 } );
}


#------------------------------------------------------------------------------
# This package exists just to delete the temporary directory.
#------------------------------------------------------------------------------
package Temp::Delete;

use File::Temp   0.12   qw(tempdir);

sub new
{
  my $this = shift;
  my $class = ref($this) || $this;
  my $self = {};
  bless $self, $class;

  my $temp_dir = tempdir("svncopy_XXXXXXXXXX", TMPDIR => 1);
  
  $self->{tempdir} = $temp_dir;
  
  return $self;
}

sub temp_dir
{
  my $self = shift;
  return $self->{tempdir};
}

sub DESTROY
{
  my $self = shift;
  my $temp_dir = $self->{tempdir};
  if ( scalar( @errors ) )
  {
    print "Leaving $temp_dir for inspection\n";
  }
  else
  {
    info( "Cleaning up $temp_dir\n" );
    File::Path::rmtree([$temp_dir], 0, 0);
  }
}


#------------------------------------------------------------------------------
# Documentation follows, in pod format.
#------------------------------------------------------------------------------
__END__

=head1 NAME

B<svncopy> - extended form of B<svn copy>

=head1 SYNOPSIS

B<svncopy.pl> [option ...] source [source ...] destination

This script copies one Subversion location or set of locations to another,
in the same way as B<svn copy>.  Using the script allows more advanced operations,
in particular allowing svn:externals to be dealt with properly for branching
or tagging.

 Parameters:
  source         Subversion item to copy from.
                 Multiple sources can be given.
  destination    Destination to copy to.

 Options:
  -t [--tag]             : set svn:externals to current version
     [--pin-externals ]
  -b [--branch]          : update fully contained svn:externals
     [--update-externals]
  -m [--message] arg     : specify commit message ARG
  -F [--file] arg        : read data from file ARG
  -r [--revision] arg    : ARG (some commands also take ARG1:ARG2 range)
                           A revision argument can be one of:
                             NUMBER       revision number
                             "{" DATE "}" revision at start of the date
                             "HEAD"       latest in repository
                             "BASE"       base rev of item's working copy
                             "COMMITTED"  last commit at or before BASE
                             "PREV"       revision just before COMMITTED
  -q [--quiet]           : print as little as possible
  --username arg         : specify a username ARG
  --password arg         : specify a password ARG
  --no-auth-cache        : do not cache authentication tokens
  --force-log            : force validity of log message source
  --encoding arg         : treat value as being in charset encoding ARG
  --config-dir arg       : read user config files from directory ARG
  --[no]verbose          : sets the script to give lots of output

=head1 PARAMETERS

=over

=item B<source>

The subversion item or items to copy from.

=item B<destination>

The destination URL to copy to.

=back

=head1 OPTIONS

=over

=item B<-t [--pin-externals or --tag]>

Update any svn:externals to ensure they have a version number,
using the current destination version if none is already specified.
Useful for tagging operations.

=item B<-b [--update-externals or --branch]>

Update any unversioned svn:externals which point to a location
within one of the sources so that they point to the corresponding
location within the destination.

Note: --pin-externals and --update-externals are mutually exclusive.

=item B<-m [--message] arg>

Specify commit message ARG

=item B<-F [--file] arg>

Read data from file ARG

=item B<-r [--revision] arg>

ARG (some commands also take ARG1:ARG2 range)
A revision argument can be one of:

    NUMBER       revision number
    "{" DATE "}" revision at start of the date
    "HEAD"       latest in repository
    "BASE"       base rev of item's working copy
    "COMMITTED"  last commit at or before BASE
    "PREV"       revision just before COMMITTED

=item B<-q [--quiet]>

Print as little as possible

=item B<--username arg>

Specify a username ARG

=item B<--password arg>

Specify a password ARG

=item B<--no-auth-cache>

Do not cache authentication tokens

=item B<--force-log>

Force validity of log message source

=item B<--encoding arg>

Treat value as being in charset encoding ARG

=item B<--config-dir arg>

Read user configuration files from directory ARG

=item B<--[no]verbose>

Sets the script to give lots of output when it runs.

=item B<--help>

Print a brief help message and exits.

=back

=head1 DESCRIPTION

This script performs an B<svn copy> command.  It allows extra processing to get
around the following limitations of B<svn copy>:

svn:externals definitions are (in Subversion 1.0 and 1.1 at least) absolute paths.
This means that an B<svn copy> used as a branch or tag operation on a tree with
embedded svn:externals will not do what is expected.  The svn:externals
will still point at the original location and will not be pinned down.

B<svncopy --update-externals> (or B<svncopy --branch>) will update any
unversioned svn:externals in the destination tree which point at locations
within one of the source trees so that they point to the corresponding locations
within the destination tree instead.  This effectively updates the reference to
point to the destination tree, and is the behaviour you want for branching.

B<svncopy --pin-externals> (or B<svncopy --tag>) will update any unversioned
svn:externals in the destination tree to contain the current version of the
directory listed in the svn:externals definition.  This effectively pins
the reference to the current version, and is the behaviour you want for tagging.

Note: both forms of the command leave unchanged any svn:externals which
already contain a version number.

=cut

#------------------------------- END OF FILE ----------------------------------
